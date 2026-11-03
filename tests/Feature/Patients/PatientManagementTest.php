<?php

namespace Tests\Feature\Patients;

use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\AppointmentResourceAllocation;
use App\Models\Patient;
use App\Models\TreatmentService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_create_search_and_keep_shared_mobile_numbers_separate(): void
    {
        $this->signIn(UserRole::Receptionist);

        $this->post(route('patients.store'), [
            'first_name' => 'سارا',
            'last_name' => 'کریمی',
            'mobile' => '۰۹۱۲۱۲۳۴۵۶۷',
            'birth_date' => '1403/01/01',
            'status' => 'active',
        ])->assertRedirect();

        $this->post(route('patients.store'), [
            'first_name' => 'نیما',
            'last_name' => 'کریمی',
            'mobile' => '09121234567',
            'status' => 'active',
        ])->assertRedirect()->assertSessionHas('duplicate_notice');

        $this->assertSame(2, Patient::query()->count());
        $this->assertSame(2, Patient::query()->where('mobile_normalized', '09121234567')->count());

        $patients = Patient::query()->orderBy('id')->get();
        $this->assertNotSame($patients[0]->id, $patients[1]->id);
        $this->assertSame('09121234567', $patients[0]->mobile_normalized);
        $this->assertSame('09121234567', $patients[1]->mobile_normalized);
        $this->assertNotNull($patients[0]->birth_date);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $patients[0]->birth_date->format('Y-m-d'));

        $this->get(route('patients.index', ['q' => '09121234567']))
            ->assertOk()
            ->assertSee('سارا')
            ->assertSee('نیما');
    }

    public function test_patient_validation_and_archive_restore(): void
    {
        $this->signIn(UserRole::Receptionist);

        $this->post(route('patients.store'), [
            'first_name' => '',
            'last_name' => '',
            'status' => 'active',
        ])->assertSessionHasErrors(['first_name', 'last_name']);

        $this->post(route('patients.store'), [
            'first_name' => 'هدی',
            'last_name' => 'صالحی',
            'status' => 'active',
        ])->assertRedirect();

        $patient = Patient::query()->firstOrFail();
        $this->assertSame('P-'.str_pad((string) $patient->id, 6, '0', STR_PAD_LEFT), $patient->code);

        $this->post(route('patients.archive', $patient))->assertRedirect(route('patients.index'));
        $this->assertSoftDeleted($patient);
        $this->get(route('patients.index'))->assertDontSee('هدی');
        $this->get(route('patients.index', ['archived' => 1]))->assertSee('هدی');

        $this->post(route('patients.restore', $patient->id))->assertRedirect(route('patients.show', $patient));
        $this->assertNotSoftDeleted($patient);
    }

    public function test_specialist_can_view_allocated_patients_but_not_create(): void
    {
        $this->signIn(UserRole::Receptionist);
        $this->post(route('patients.store'), [
            'first_name' => 'پارسا',
            'last_name' => 'جعفری',
            'status' => 'active',
        ]);
        $patient = Patient::query()->firstOrFail();
        $unrelated = Patient::query()->create([
            'code' => 'P-900099',
            'first_name' => 'بی‌ربط',
            'last_name' => 'جعفری',
            'status' => 'active',
        ]);

        $specialist = User::factory()->create(['role' => UserRole::Specialist, 'is_active' => true]);
        $service = TreatmentService::query()->create([
            'code' => 'VIEW',
            'name' => 'خدمت مشاهده',
            'default_duration_minutes' => 30,
            'buffer_minutes' => 0,
            'requires_specialist' => true,
            'required_clinical_staff_count' => 0,
            'is_active' => true,
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

        $this->actingAs($specialist);
        $this->get(route('patients.show', $patient))->assertOk();
        $this->get(route('patients.show', $unrelated))->assertForbidden();
        $this->get(route('patients.create'))->assertForbidden();
    }
}
