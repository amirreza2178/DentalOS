<?php

namespace Tests\Feature\Clinical;

use App\Enums\FollowUpAuthorization;
use App\Enums\FollowUpStatus;
use App\Enums\Qualification;
use App\Enums\ResourceType;
use App\Enums\TreatmentPlanStatus;
use App\Enums\TreatmentTaskStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AppointmentResourceAllocation;
use App\Models\FollowUp;
use App\Models\Patient;
use App\Models\TreatmentPlan;
use App\Models\TreatmentTask;
use App\Models\User;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

class ClinicalWorkflowTest extends TestCase
{
    use BuildsClinicSchedule;
    use RefreshDatabase;

    public function test_specialist_can_create_treatment_plan_and_receptionist_cannot(): void
    {
        $clinic = $this->makeClinic();
        $specialist = $clinic['specialist'];
        $patient = $this->linkedPatient($clinic);

        $this->actingAs($specialist)
            ->post(route('clinical.plans.store', $patient), [
                'clinical_summary' => 'تنظیم براکت بالا در جلسه بعد',
                'operational_summary' => 'نیاز به هماهنگی یونیت ارتو',
                'status' => TreatmentPlanStatus::Active->value,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('treatment_plans', [
            'patient_id' => $patient->id,
            'created_by_user_id' => $specialist->id,
            'clinical_summary' => 'تنظیم براکت بالا در جلسه بعد',
        ]);

        $receptionist = User::factory()->create(['role' => UserRole::Receptionist, 'is_active' => true]);
        $this->actingAs($receptionist)
            ->post(route('clinical.plans.store', $patient), [
                'clinical_summary' => 'تلاش غیرمجاز',
                'status' => TreatmentPlanStatus::Active->value,
            ])
            ->assertForbidden();
    }

    public function test_task_assignment_requires_matching_qualification(): void
    {
        $clinic = $this->makeClinic();
        $specialist = $clinic['specialist'];
        $patient = $this->linkedPatient($clinic);
        $plan = $this->plan($patient, $specialist);
        $student = $clinic['assistant'];
        $qualified = $clinic['assistantTwo'];

        $this->actingAs($specialist)
            ->post(route('clinical.plans.tasks.store', $plan), [
                'title' => 'پاکسازی سبک',
                'instructions' => 'طبق دستور متخصص',
                'required_qualification' => Qualification::QualifiedClinicalPersonnel->value,
                'assignee_user_id' => $student->id,
            ])
            ->assertSessionHasErrors('assignee_user_id');

        $this->assertDatabaseCount('treatment_tasks', 0);

        $this->actingAs($specialist)
            ->post(route('clinical.plans.tasks.store', $plan), [
                'title' => 'پاکسازی سبک',
                'instructions' => 'طبق دستور متخصص',
                'required_qualification' => Qualification::QualifiedClinicalPersonnel->value,
                'assignee_user_id' => $qualified->id,
            ])
            ->assertRedirect();

        $task = TreatmentTask::query()->first();
        $this->assertNotNull($task);
        $this->assertSame(TreatmentTaskStatus::Assigned, $task->status);
        $this->assertSame($qualified->id, $task->assignee_user_id);
    }

    public function test_clinical_staff_only_sees_own_assigned_tasks_and_can_complete_them(): void
    {
        $clinic = $this->makeClinic();
        $specialist = $clinic['specialist'];
        $patient = $this->linkedPatient($clinic);
        $plan = $this->plan($patient, $specialist);
        $mine = $clinic['assistantTwo'];
        $other = $clinic['assistant'];

        $myTask = TreatmentTask::query()->create([
            'treatment_plan_id' => $plan->id,
            'title' => 'وظیفه من',
            'required_qualification' => Qualification::QualifiedClinicalPersonnel,
            'assignee_user_id' => $mine->id,
            'assigned_by_user_id' => $specialist->id,
            'status' => TreatmentTaskStatus::Assigned,
        ]);
        $otherTask = TreatmentTask::query()->create([
            'treatment_plan_id' => $plan->id,
            'title' => 'وظیفه دیگری',
            'required_qualification' => Qualification::Student,
            'assignee_user_id' => $other->id,
            'assigned_by_user_id' => $specialist->id,
            'status' => TreatmentTaskStatus::Assigned,
        ]);

        $this->actingAs($mine)
            ->get(route('clinical.tasks.index'))
            ->assertOk()
            ->assertSee('وظیفه من')
            ->assertDontSee('وظیفه دیگری');

        $this->actingAs($mine)
            ->get(route('clinical.tasks.show', $otherTask))
            ->assertForbidden();

        $this->actingAs($mine)
            ->post(route('clinical.tasks.status', $myTask), [
                'status' => TreatmentTaskStatus::InProgress->value,
            ])
            ->assertRedirect();

        $this->actingAs($mine)
            ->post(route('clinical.tasks.status', $myTask->fresh()), [
                'status' => TreatmentTaskStatus::Completed->value,
                'completion_note' => 'انجام شد بدون عارضه',
            ])
            ->assertRedirect();

        $this->assertSame(TreatmentTaskStatus::Completed, $myTask->fresh()->status);
        $this->assertNotNull($myTask->fresh()->completed_at);

        $this->actingAs($mine)
            ->post(route('clinical.tasks.status', $myTask->fresh()), [
                'status' => TreatmentTaskStatus::Assigned->value,
            ])
            ->assertSessionHasErrors('status');
    }

    public function test_follow_up_is_created_without_auto_booking_and_schedules_only_with_authorization(): void
    {
        $clinic = $this->makeClinic();
        $specialist = $clinic['specialist'];
        $patient = $this->linkedPatient($clinic);
        $receptionist = User::factory()->create(['role' => UserRole::Receptionist, 'is_active' => true]);
        $jalali = app(JalaliCalendar::class)->formatDate('2026-11-07');

        $this->actingAs($specialist)
            ->post(route('clinical.follow-ups.store', $patient), [
                'proposed_window_start' => $jalali,
                'proposed_window_end' => $jalali,
                'treatment_service_id' => $clinic['service']->id,
                'operational_note' => 'هماهنگی مراجعه کنترل',
            ])
            ->assertRedirect();

        $followUp = FollowUp::query()->first();
        $this->assertNotNull($followUp);
        $this->assertSame(FollowUpStatus::Proposed, $followUp->status);
        $this->assertNull($followUp->converted_appointment_id);
        $this->assertSame(0, Appointment::query()->where('follow_up_id', $followUp->id)->count());

        $this->actingAs($receptionist)
            ->get(route('clinical.follow-ups.index', ['actionable' => 1]))
            ->assertOk()
            ->assertSee('هماهنگی مراجعه کنترل');

        $appointment = $this->reserveAt($clinic, '09:00', [
            'patient' => $patient,
            'followUp' => $followUp,
            'authorization' => FollowUpAuthorization::Receptionist,
            'actor' => $receptionist,
        ]);

        $this->assertSame($followUp->id, $appointment->follow_up_id);
        $this->assertSame(FollowUpStatus::Scheduled, $followUp->fresh()->status);
        $this->assertSame($appointment->id, $followUp->fresh()->converted_appointment_id);
    }

    public function test_specialist_patient_access_is_limited_to_allocated_patients(): void
    {
        $clinic = $this->makeClinic();
        $specialist = $clinic['specialist'];
        $linked = $this->linkedPatient($clinic);
        $other = Patient::query()->create([
            'code' => 'P-880002',
            'first_name' => 'بی‌ارتباط',
            'last_name' => 'آزمایش',
            'status' => 'active',
        ]);

        $this->actingAs($specialist)
            ->get(route('patients.index'))
            ->assertOk()
            ->assertSee($linked->code)
            ->assertDontSee($other->code);

        $this->actingAs($specialist)
            ->get(route('patients.show', $other))
            ->assertForbidden();

        $this->actingAs($specialist)
            ->get(route('patients.clinical-notes', $other))
            ->assertForbidden();

        $this->actingAs($specialist)
            ->get(route('patients.show', $linked))
            ->assertOk();

        $this->actingAs($specialist)
            ->get(route('reception.search', ['q' => $other->first_name]))
            ->assertForbidden();
    }

    public function test_security_headers_are_present_on_management_pages(): void
    {
        $this->signIn(UserRole::Admin);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_receptionist_cannot_see_clinical_summary_on_plan_page(): void
    {
        $clinic = $this->makeClinic();
        $specialist = $clinic['specialist'];
        $patient = $this->linkedPatient($clinic);
        $plan = $this->plan($patient, $specialist, 'متن حساس بالینی محرمانه');
        $receptionist = User::factory()->create(['role' => UserRole::Receptionist, 'is_active' => true]);

        $this->actingAs($receptionist)
            ->get(route('clinical.plans.show', $plan))
            ->assertOk()
            ->assertDontSee('متن حساس بالینی محرمانه')
            ->assertSee('نیاز به هماهنگی یونیت');
    }

    /**
     * @param  array<string, mixed>  $clinic
     */
    private function linkedPatient(array $clinic): Patient
    {
        $patient = Patient::query()->create([
            'code' => 'P-880001',
            'first_name' => 'سارا',
            'last_name' => 'آزمایش',
            'status' => 'active',
        ]);

        $appointment = Appointment::query()->create([
            'patient_id' => $patient->id,
            'treatment_service_id' => $clinic['service']->id,
            'starts_at' => '2026-11-07 08:00:00',
            'ends_at' => '2026-11-07 08:40:00',
            'status' => 'confirmed',
            'source' => 'receptionist',
        ]);

        AppointmentResourceAllocation::query()->create([
            'appointment_id' => $appointment->id,
            'resource_type' => ResourceType::Specialist->value,
            'resource_id' => $clinic['specialist']->id,
            'starts_at' => '2026-11-07 08:00:00',
            'ends_at' => '2026-11-07 08:45:00',
            'is_required' => true,
        ]);

        return $patient;
    }

    private function plan(Patient $patient, User $specialist, string $clinical = 'برنامه آزمایشی'): TreatmentPlan
    {
        return TreatmentPlan::query()->create([
            'patient_id' => $patient->id,
            'created_by_user_id' => $specialist->id,
            'status' => TreatmentPlanStatus::Active,
            'clinical_summary' => $clinical,
            'operational_summary' => 'نیاز به هماهنگی یونیت',
        ]);
    }
}
