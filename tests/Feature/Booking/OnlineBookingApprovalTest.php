<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\ApproveOnlineAppointment;
use App\Actions\Booking\BookOnlineAppointment;
use App\Actions\Booking\RejectOnlineAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Exceptions\Scheduling\OnlineBookingConflictException;
use App\Models\Appointment;
use App\Models\AppointmentResourceAllocation;
use App\Models\User;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class OnlineBookingApprovalTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-03 09:00:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_receptionist_can_list_approve_and_reject_online_requests(): void
    {
        $clinic = $this->makeClinic();
        $pending = $this->bookOnline($clinic, '08:00');
        $other = $this->bookOnline($clinic, '08:40');

        $receptionist = $this->signIn(UserRole::Receptionist);

        $this->get(route('booking.requests.index'))
            ->assertOk()
            ->assertSee($pending->patient->first_name)
            ->assertSee($other->patient->first_name);

        $this->post(route('booking.requests.approve', $pending))
            ->assertRedirect(route('booking.requests.show', $pending));

        $pending->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $pending->status);
        $this->assertSame($receptionist->id, $pending->approved_by_user_id);
        $this->assertSame(
            3,
            AppointmentResourceAllocation::query()->where('appointment_id', $pending->id)->count(),
        );

        $this->post(route('booking.requests.reject', $other), [
            'decision_reason' => 'شماره تماس پاسخگو نبود',
        ])->assertRedirect(route('booking.requests.show', $other));

        $other->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $other->status);
        $this->assertSame('شماره تماس پاسخگو نبود', $other->decision_reason);

        $slotReuse = $this->bookOnline($clinic, '08:40');
        $this->assertSame(AppointmentStatus::PendingApproval, $slotReuse->status);
    }

    public function test_unauthorized_roles_cannot_decide_online_requests(): void
    {
        $clinic = $this->makeClinic();
        $pending = $this->bookOnline($clinic, '08:00');

        $this->signIn(UserRole::Specialist);
        $this->get(route('booking.requests.index'))->assertForbidden();
        $this->post(route('booking.requests.approve', $pending))->assertForbidden();

        $this->signIn(UserRole::ClinicalStaff);
        $this->post(route('booking.requests.reject', $pending))->assertForbidden();
    }

    public function test_repeat_approve_is_idempotent_and_concurrent_conflict_is_clear(): void
    {
        $clinic = $this->makeClinic();
        $pending = $this->bookOnline($clinic, '08:00');
        $first = User::factory()->create(['role' => UserRole::Receptionist, 'is_active' => true]);
        $second = User::factory()->create(['role' => UserRole::Receptionist, 'is_active' => true]);

        $approved = app(ApproveOnlineAppointment::class)->approve($pending, $first);
        $this->assertSame(AppointmentStatus::Confirmed, $approved->status);

        $again = app(ApproveOnlineAppointment::class)->approve($pending->fresh(), $first);
        $this->assertSame(AppointmentStatus::Confirmed, $again->status);
        $this->assertSame($first->id, $again->approved_by_user_id);

        $this->expectException(OnlineBookingConflictException::class);
        app(RejectOnlineAppointment::class)->reject($pending->fresh(), $second, 'دیر شد');
    }

    public function test_specialist_only_sees_own_allocated_appointments(): void
    {
        $clinic = $this->makeClinic();
        $mine = $this->reserveAt($clinic, '08:00');
        $theirs = $this->reserveAt($clinic, '10:40', [
            'patient' => $clinic['otherPatient'],
            'specialist' => $clinic['specialistTwo'],
            'staff' => [$clinic['assistantTwo']->id],
            'chair' => $clinic['orthoChair'],
        ]);

        $this->actingAs($clinic['specialist']);
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');
        $this->get(route('appointments.index', ['date' => $jalali]))
            ->assertOk()
            ->assertSee($mine->patient->fullName())
            ->assertDontSee($theirs->patient->fullName());

        $this->get(route('appointments.show', $theirs))->assertForbidden();
        $this->get(route('appointments.show', $mine))->assertOk();
    }

    /**
     * @param  array<string, mixed>  $clinic
     */
    private function bookOnline(array $clinic, string $time): Appointment
    {
        $stamp = str_replace(':', '', $time);

        return app(BookOnlineAppointment::class)->book([
            'first_name' => 'مینا',
            'last_name' => 'کاظمی'.$stamp,
            'mobile' => '0912'.str_pad($stamp, 7, '0'),
            'treatment_service_id' => $clinic['service']->id,
            'starts_at' => Carbon::parse('2026-11-07 '.$time.':00', 'UTC'),
            'patient_notes' => null,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }
}
