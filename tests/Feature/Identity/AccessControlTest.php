<?php

namespace Tests\Feature\Identity;

use App\Enums\UserRole;
use App\Models\Patient;
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

    public function test_specialist_can_open_clinical_notes_page_without_a_stored_note(): void
    {
        $this->signIn(UserRole::Specialist);
        $patient = Patient::query()->create([
            'code' => 'P-900003',
            'first_name' => 'کیان',
            'last_name' => 'نوری',
            'status' => 'active',
        ]);

        $this->get(route('patients.clinical-notes', $patient))
            ->assertOk()
            ->assertSee('یادداشت بالینی در این مرحله ثبت نمی‌شود');
    }
}
