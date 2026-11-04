<?php

namespace Tests\Feature\Scheduling;

use App\Actions\Reception\CheckInAppointment;
use App\Actions\Reception\CompleteVisit;
use App\Actions\Reception\RemoveFromQueue;
use App\Actions\Reception\StartVisitService;
use App\Actions\Scheduling\CancelAppointment;
use App\Actions\Scheduling\CancelAppointmentResult;
use App\Enums\AppointmentStatus;
use App\Enums\CancellationReason;
use App\Enums\OperationalEventType;
use App\Enums\QueueStatus;
use App\Enums\UserRole;
use App\Enums\VisitStatus;
use App\Exceptions\Reception\ReceptionConflictException;
use App\Models\OperationalEvent;
use App\Models\PatientVisit;
use App\Models\WaitingQueueEntry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class CancelAppointmentHardeningTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-07 07:30:00', 'Asia/Tehran'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_confirmed_appointment_cancels_with_explicit_outcome_and_single_event(): void
    {
        $clinic = $this->makeClinic();
        $actor = $this->signIn(UserRole::Receptionist);
        $appointment = $this->reserveAt($clinic, '08:00');

        $first = app(CancelAppointment::class)->cancel(
            $appointment,
            $actor,
            CancellationReason::PatientRequest,
            'تماس',
        );

        $this->assertTrue($first->wasNewlyCancelled());
        $this->assertSame(CancelAppointmentResult::OUTCOME_CANCELLED, $first->outcome);
        $this->assertSame(AppointmentStatus::Cancelled, $first->appointment->status);
        $this->assertSame('cancel:patient_request|تماس', $first->appointment->decision_reason);

        $second = app(CancelAppointment::class)->cancel(
            $appointment->fresh(),
            $actor,
            CancellationReason::Other,
            'نباید جایگزین شود',
        );

        $this->assertTrue($second->wasAlreadyCancelled());
        $this->assertSame('cancel:patient_request|تماس', $appointment->fresh()->decision_reason);
        $this->assertSame(1, OperationalEvent::query()
            ->where('event_type', 'appointment_cancelled')
            ->where('subject_id', $appointment->id)
            ->count());
    }

    public function test_queue_removal_preserves_checked_in_and_allows_cancellation(): void
    {
        $clinic = $this->makeClinic();
        $actor = $this->signIn(UserRole::Receptionist);
        $appointment = $this->reserveAt($clinic, '08:00');
        $checkIn = app(CheckInAppointment::class)->handle($appointment, $actor);

        app(RemoveFromQueue::class)->handle($checkIn['queue'], $actor);

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->status);
        $this->assertSame(VisitStatus::Cancelled, $checkIn['visit']->fresh()->status);
        $this->assertSame(QueueStatus::Removed, $checkIn['queue']->fresh()->status);
        $this->assertTrue(OperationalEvent::query()
            ->where('event_type', OperationalEventType::QueueEntryRemoved->value)
            ->where('subject_id', $checkIn['queue']->id)
            ->exists());

        $result = app(CancelAppointment::class)->cancel(
            $appointment->fresh(),
            $actor,
            CancellationReason::PatientRequest,
        );

        $this->assertTrue($result->wasNewlyCancelled());
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);
        $this->assertSame(1, OperationalEvent::query()
            ->where('event_type', 'appointment_cancelled')
            ->where('subject_id', $appointment->id)
            ->count());
    }

    public function test_active_queue_and_active_visit_block_cancellation(): void
    {
        $clinic = $this->makeClinic();
        $actor = $this->signIn(UserRole::Receptionist);
        $appointment = $this->reserveAt($clinic, '08:00');
        app(CheckInAppointment::class)->handle($appointment, $actor);

        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->fresh()->status);

        try {
            app(CancelAppointment::class)->cancel($appointment->fresh(), $actor, CancellationReason::PatientRequest);
            $this->fail('Active visit/queue must block cancellation.');
        } catch (ReceptionConflictException $exception) {
            $this->assertStringContainsString('صف', $exception->getMessage());
        }

        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->fresh()->status);
        $this->assertSame(0, OperationalEvent::query()->where('event_type', 'appointment_cancelled')->where('subject_id', $appointment->id)->count());
    }

    public function test_completed_visit_cannot_be_cancelled_via_appointment_cancel(): void
    {
        $clinic = $this->makeClinic();
        $actor = $this->signIn(UserRole::Receptionist);
        $appointment = $this->reserveAt($clinic, '08:00');
        $checkIn = app(CheckInAppointment::class)->handle($appointment, $actor);
        app(StartVisitService::class)->handle($checkIn['visit'], $actor);
        app(CompleteVisit::class)->handle($checkIn['visit']->fresh(), $actor);

        $this->assertSame(VisitStatus::Completed, $checkIn['visit']->fresh()->status);
        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);

        try {
            app(CancelAppointment::class)->cancel($appointment->fresh(), $actor, CancellationReason::PatientRequest);
            $this->fail('Completed visit must not be cancellable.');
        } catch (ReceptionConflictException|\App\Exceptions\Scheduling\InvalidAppointmentTransitionException $exception) {
            $this->assertTrue(true);
        }

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
        $this->assertSame(0, OperationalEvent::query()->where('event_type', 'appointment_cancelled')->where('subject_id', $appointment->id)->count());
    }

    public function test_orphan_checked_in_without_visit_is_conflict(): void
    {
        $clinic = $this->makeClinic();
        $actor = $this->signIn(UserRole::Receptionist);
        $appointment = $this->reserveAt($clinic, '08:00');
        $appointment->status = AppointmentStatus::CheckedIn;
        $appointment->save();

        $this->assertNull(PatientVisit::query()->where('appointment_id', $appointment->id)->first());

        try {
            app(CancelAppointment::class)->cancel($appointment->fresh(), $actor, CancellationReason::PatientRequest);
            $this->fail('Orphan checked-in must conflict.');
        } catch (ReceptionConflictException $exception) {
            $this->assertStringContainsString('مدیر', $exception->getMessage());
        }

        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->fresh()->status);
        $this->assertSame(0, OperationalEvent::query()->where('event_type', 'appointment_cancelled')->where('subject_id', $appointment->id)->count());
    }

    public function test_cancelled_visit_without_removed_queue_does_not_authorize_cancel(): void
    {
        $clinic = $this->makeClinic();
        $actor = $this->signIn(UserRole::Receptionist);
        $appointment = $this->reserveAt($clinic, '08:00');
        $appointment->status = AppointmentStatus::CheckedIn;
        $appointment->save();

        $visit = PatientVisit::query()->create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'status' => VisitStatus::Cancelled,
            'checked_in_by_user_id' => $actor->id,
            'arrived_at' => now(),
            'completed_at' => now(),
        ]);

        WaitingQueueEntry::query()->create([
            'patient_visit_id' => $visit->id,
            'queue_date' => '2026-11-07',
            'position' => 1,
            'status' => QueueStatus::Completed,
            'arrived_at' => now(),
            'completed_at' => now(),
        ]);

        try {
            app(CancelAppointment::class)->cancel($appointment->fresh(), $actor, CancellationReason::PatientRequest);
            $this->fail('Visit cancelled without removed-queue evidence must not authorize cancel.');
        } catch (ReceptionConflictException $exception) {
            $this->assertStringContainsString('خروج صریح از صف', $exception->getMessage());
        }

        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->fresh()->status);
    }

    public function test_specialist_and_null_actor_direct_invocation_are_unauthorized(): void
    {
        $clinic = $this->makeClinic();
        $appointment = $this->reserveAt($clinic, '08:00');

        try {
            app(CancelAppointment::class)->cancel($appointment, null, CancellationReason::PatientRequest);
            $this->fail('Null actor must be unauthorized.');
        } catch (AuthorizationException) {
            // expected
        }

        $specialist = $this->signIn(UserRole::Specialist);
        $this->expectException(AuthorizationException::class);
        app(CancelAppointment::class)->cancel($appointment, $specialist, CancellationReason::PatientRequest);
    }
}
