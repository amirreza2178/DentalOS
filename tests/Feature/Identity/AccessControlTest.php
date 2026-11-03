<?php

namespace Tests\Feature\Identity;

use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AppointmentResourceAllocation;
use App\Models\Patient;
use App\Models\TreatmentService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_open_management_pages(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('patients.index'))->assertRedirect(route('login'));
        $this->get(route('settings.edit'))->assertRedirect(route('login'));
    }

    public function test_inactive_accounts_cannot_sign_in(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_patient_accounts_cannot_open_the_management_dashboard(): void
    {
        $this->signIn(UserRole::Patient);

        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('patients.index'))->assertForbidden();
    }

    public function test_a_patient_account_cannot_view_another_patient_record(): void
    {
        $owner = User::factory()->create(['role' => UserRole::Patient]);
        $other = User::factory()->create(['role' => UserRole::Patient]);
        $record = Patient::query()->create([
            'user_id' => $owner->id,
            'code' => 'P-900001',
            'first_name' => 'مالک',
            'last_name' => 'پرونده',
            'status' => 'active',
        ]);

        $this->assertTrue($owner->can('view', $record));
        $this->assertFalse($other->can('view', $record));
    }

    public function test_receptionists_cannot_open_clinical_notes_or_settings(): void
    {
        $this->signIn(UserRole::Receptionist);
        $patient = Patient::query()->create([
            'code' => 'P-900002',
            'first_name' => 'نسترن',
            'last_name' => 'راد',
            'status' => 'active',
        ]);

        $this->get(route('patients.clinical-notes', $patient))->assertForbidden();
        $this->get(route('settings.edit'))->assertForbidden();
        $this->put(route('settings.update'), ['clinic_name' => 'تلاش غیرمجاز'])->assertForbidden();
    }

    public function test_clinical_staff_cannot_browse_patients_or_create_accounts(): void
    {
        $this->signIn(UserRole::ClinicalStaff);

        $this->get(route('patients.index'))->assertForbidden();
        $this->get(route('staff.create', 'specialists'))->assertForbidden();
        $this->post(route('staff.store', 'clinical-staff'), [
            'name' => 'ارتقای نقش',
            'email' => 'escalate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => '1',
            'role' => 'admin',
            'qualification' => 'licensed_dentist',
        ])->assertForbidden();
    }

    public function test_specialist_can_open_clinical_notes_only_for_allocated_patients(): void
    {
        $specialist = $this->signIn(UserRole::Specialist);
        $service = TreatmentService::query()->create([
            'code' => 'TST',
            'name' => 'خدمت آزمایش',
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'requires_specialist' => true,
            'required_clinical_staff_count' => 0,
            'is_active' => true,
        ]);
        $patient = Patient::query()->create([
            'code' => 'P-900003',
            'first_name' => 'کیان',
            'last_name' => 'نوری',
            'status' => 'active',
        ]);
        $unrelated = Patient::query()->create([
            'code' => 'P-900004',
            'first_name' => 'بی‌ربط',
            'last_name' => 'نوری',
            'status' => 'active',
        ]);

        $appointment = Appointment::query()->create([
            'patient_id' => $patient->id,
            'treatment_service_id' => $service->id,
            'starts_at' => '2026-11-07 08:00:00',
            'ends_at' => '2026-11-07 08:30:00',
            'status' => 'confirmed',
            'source' => 'receptionist',
        ]);
        AppointmentResourceAllocation::query()->create([
            'appointment_id' => $appointment->id,
            'resource_type' => ResourceType::Specialist->value,
            'resource_id' => $specialist->id,
            'starts_at' => '2026-11-07 08:00:00',
            'ends_at' => '2026-11-07 08:30:00',
            'is_required' => true,
        ]);

        $this->get(route('patients.clinical-notes', $patient))
            ->assertOk()
            ->assertSee('یادداشت بالینی در این مرحله ثبت نمی‌شود');

        $this->get(route('patients.clinical-notes', $unrelated))->assertForbidden();
        $this->get(route('patients.show', $unrelated))->assertForbidden();
        $this->get(route('patients.index'))
            ->assertOk()
            ->assertSee('کیان', false)
            ->assertSee('P-900003', false)
            ->assertDontSee('P-900004', false);
        $this->get(route('reception.search', ['q' => 'کیان']))->assertForbidden();
    }
}
