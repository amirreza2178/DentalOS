<?php

namespace Tests\Feature\Reception;

use App\Actions\Reception\CallNextInQueue;
use App\Actions\Reception\CheckInAppointment;
use App\Actions\Reception\CompleteVisit;
use App\Actions\Reception\StartVisitService;
use App\Actions\Scheduling\CancelAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\OperationalEventType;
use App\Enums\QueueStatus;
use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Exceptions\Reception\ReceptionConflictException;
use App\Models\OperationalEvent;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\WaitingQueueEntry;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class ReceptionDeskTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    public function test_search_finds_patients_by_name_mobile_and_code_without_merging_shared_mobile(): void
    {
        $this->signIn(UserRole::Receptionist);
        $first = Patient::query()->create([
            'code' => 'P-R1',
            'first_name' => 'سارا',
            'last_name' => 'پذیرش',
            'mobile' => '09120001111',
            'mobile_normalized' => '09120001111',
            'status' => 'active',
        ]);
        $second = Patient::query()->create([
            'code' => 'P-R2',
            'first_name' => 'نیما',
            'last_name' => 'پذیرش',
            'mobile' => '09120001111',
            'mobile_normalized' => '09120001111',
            'status' => 'active',
        ]);

        $this->get(route('reception.search', ['q' => 'سارا']))
            ->assertOk()
            ->assertSee('سارا')
            ->assertDontSee('نیما پذیرش', false);

        $this->get(route('reception.search', ['q' => '09120001111']))
            ->assertOk()
            ->assertSee('سارا')
            ->assertSee('نیما')
            ->assertSee('P-R1')
            ->assertSee('P-R2');

        $this->get(route('reception.search', ['q' => 'P-R2']))
            ->assertOk()
            ->assertSee('نیما')
            ->assertSee((string) $second->id, false);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, Patient::query()->where('mobile_normalized', '09120001111')->count());
    }

    public function test_check_in_creates_visit_and_queue_and_is_idempotent(): void
    {
        $clinic = $this->makeClinic();
        $appointment = $this->reserveAt($clinic, '08:00');
        $actor = $this->signIn(UserRole::Receptionist);

        $first = app(CheckInAppointment::class)->handle($appointment, $actor);
        $this->assertTrue($first['created']);
        $this->assertSame(VisitStatus::Waiting, $first['visit']->status);
        $this->assertSame(QueueStatus::Waiting, $first['queue']->status);
        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->fresh()->status);
        $this->assertSame(1, PatientVisit::query()->count());
        $this->assertSame(1, WaitingQueueEntry::query()->count());

        $second = app(CheckInAppointment::class)->handle($appointment->fresh(), $actor);
        $this->assertFalse($second['created']);
        $this->assertSame($first['visit']->id, $second['visit']->id);
        $this->assertSame(1, PatientVisit::query()->count());
        $this->assertSame(1, WaitingQueueEntry::query()->active()->count());
        $this->assertTrue(OperationalEvent::query()->where('event_type', OperationalEventType::PatientCheckedIn)->exists());
    }

    public function test_cancelled_appointment_cannot_be_checked_in_and_http_check_in_works(): void
    {
        $clinic = $this->makeClinic();
        $appointment = $this->reserveAt($clinic, '08:00');
        $receptionist = $this->signIn(UserRole::Receptionist);
        app(CancelAppointment::class)->cancel($appointment);

        try {
            app(CheckInAppointment::class)->handle($appointment->fresh(), $receptionist);
            $this->fail('Cancelled appointment check-in should fail.');
        } catch (ReceptionConflictException $exception) {
            $this->assertStringContainsString('معتبر نیست', $exception->getMessage());
        }

        $open = $this->reserveAt($clinic, '08:40', ['patient' => $clinic['otherPatient']]);
        $this->post(route('reception.check-in', $open))
            ->assertRedirect(route('reception.visits.show', PatientVisit::query()->first()));

        $this->get(route('reception.day', ['date' => app(JalaliCalendar::class)->formatDate('2026-11-07')]))
            ->assertOk()
            ->assertSee('صف انتظار فعال')
            ->assertSee($clinic['otherPatient']->fullName());
    }

    public function test_queue_call_start_complete_and_duplicate_active_prevention(): void
    {
        $clinic = $this->makeClinic();
        $first = $this->reserveAt($clinic, '08:00');
        $second = $this->reserveAt($clinic, '08:40', [
            'patient' => $clinic['otherPatient'],
            'specialist' => $clinic['specialistTwo'],
            'staff' => [$clinic['assistantTwo']->id],
            'chair' => $clinic['orthoChair'],
        ]);
        $actor = $this->signIn(UserRole::Receptionist);

        $visitOne = app(CheckInAppointment::class)->handle($first, $actor)['visit'];
        $visitTwo = app(CheckInAppointment::class)->handle($second, $actor)['visit'];

        $called = app(CallNextInQueue::class)->handle('2026-11-07', $actor);
        $this->assertSame($visitOne->id, $called->patient_visit_id);
        $this->assertSame(QueueStatus::Called, $called->status);

        try {
            app(CallNextInQueue::class)->handle('2026-11-07', $actor);
            $this->fail('Second call should fail while one patient is called.');
        } catch (ReceptionConflictException $exception) {
            $this->assertStringContainsString('فراخوانده‌شده', $exception->getMessage());
        }

        app(StartVisitService::class)->handle($visitOne->fresh(), $actor);
        $this->assertSame(VisitStatus::InProgress, $visitOne->fresh()->status);
        $this->assertSame(AppointmentStatus::InProgress, $first->fresh()->status);

        $next = app(CallNextInQueue::class)->handle('2026-11-07', $actor);
        $this->assertSame($visitTwo->id, $next->patient_visit_id);

        app(CompleteVisit::class)->handle($visitOne->fresh(), $actor);
        $this->assertSame(VisitStatus::Completed, $visitOne->fresh()->status);
        $this->assertSame(AppointmentStatus::Completed, $first->fresh()->status);
        $this->assertSame(1, WaitingQueueEntry::query()->active()->count());
        $this->assertFalse(
            WaitingQueueEntry::query()
                ->where('patient_visit_id', $visitOne->id)
                ->active()
                ->exists(),
        );
    }

    public function test_daily_and_weekly_views_respect_date_boundaries_and_authorization(): void
    {
        $clinic = $this->makeClinic();
        $this->reserveAt($clinic, '08:00');
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->signIn(UserRole::Receptionist);
        $this->get(route('reception.day', ['date' => $jalali]))
            ->assertOk()
            ->assertSee($clinic['patient']->fullName())
            ->assertSee('08:00')
            ->assertDontSee('نوبتی برای این روز نیست');

        $this->get(route('reception.week', ['date' => $jalali]))
            ->assertOk()
            ->assertSee($clinic['patient']->fullName())
            ->assertSee('نمای هفتگی');

        $this->signIn(UserRole::ClinicalStaff);
        $this->get(route('reception.day'))->assertForbidden();

        $this->signIn(UserRole::Specialist);
        $this->get(route('reception.day', ['date' => $jalali]))->assertOk();
        $appointment = $clinic['patient']->appointments()->first();
        $this->post(route('reception.check-in', $appointment))->assertForbidden();
    }

    public function test_empty_reception_desk_shows_persian_empty_state(): void
    {
        $this->signIn(UserRole::Receptionist);

        $this->get(route('reception.day'))
            ->assertOk()
            ->assertSee('نوبتی برای این روز نیست')
            ->assertSee('صف خالی است');
    }
}
