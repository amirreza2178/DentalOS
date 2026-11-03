<?php

namespace Tests\Feature\Clinic;

use App\Enums\Qualification;
use App\Enums\UserRole;
use App\Models\Chair;
use App\Models\ClinicSetting;
use App\Models\StaffProfile;
use App\Models\TreatmentService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoundationCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinical_qualification_is_stored_explicitly_and_inactive_staff_are_hidden_by_default(): void
    {
        $this->signIn(UserRole::Admin);

        $this->post(route('staff.store', 'clinical-staff'), [
            'name' => 'مهدی دانشجو',
            'email' => 'student@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => '1',
            'qualification' => Qualification::Student->value,
            'can_take_clinical_notes' => '1',
            'role' => UserRole::Admin->value,
        ])->assertRedirect();

        $account = User::query()->where('email', 'student@example.com')->firstOrFail();
        $this->assertSame(UserRole::ClinicalStaff, $account->role);
        $this->assertSame(Qualification::Student, $account->staffProfile->qualification);
        $this->assertFalse($account->staffProfile->can_take_clinical_notes);

        $this->post(route('staff.store', 'specialists'), [
            'name' => 'دکتر غیرفعال',
            'email' => 'away@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => '0',
            'qualification' => Qualification::OrthodonticSpecialist->value,
        ])->assertRedirect();

        $this->assertSame(2, User::query()->whereIn('role', [UserRole::ClinicalStaff, UserRole::Specialist])->count());
        $this->get(route('staff.index', 'specialists'))->assertDontSee('دکتر غیرفعال');
        $this->get(route('staff.index', ['directory' => 'specialists', 'status' => 'inactive']))->assertSee('دکتر غیرفعال');
        $this->assertSame(1, StaffProfile::query()->where('qualification', Qualification::Student)->count());
    }

    public function test_chairs_are_not_assumed_to_be_six_and_service_money_is_decimal(): void
    {
        $this->assertSame(0, Chair::query()->count());
        $this->signIn(UserRole::Admin);

        $this->post(route('chairs.store'), [
            'code' => 'U1',
            'name' => 'یونیت یک',
            'is_active' => '1',
            'sort_order' => 1,
        ])->assertRedirect();
        $this->post(route('chairs.store'), [
            'code' => 'U2',
            'name' => 'یونیت دو',
            'is_active' => '0',
            'sort_order' => 2,
        ])->assertRedirect();

        $this->assertSame(2, Chair::query()->count());
        $this->assertNotSame(6, Chair::query()->count());
        $this->get(route('chairs.index'))->assertSee('یونیت یک')->assertDontSee('یونیت دو');

        $this->post(route('services.store'), [
            'code' => 'ADJ',
            'name' => 'تنظیم براکت',
            'default_duration_minutes' => 45,
            'base_price' => '1500000.50',
            'is_active' => '1',
        ])->assertRedirect();

        $service = TreatmentService::query()->firstOrFail();
        $this->assertSame(45, $service->default_duration_minutes);
        $this->assertSame('1500000.50', $service->base_price);
        $this->assertNotSame(60, $service->default_duration_minutes);
    }

    public function test_settings_do_not_invent_currency_or_timezone(): void
    {
        $this->signIn(UserRole::Admin);

        $this->put(route('settings.update'), [
            'clinic_name' => 'کلینیک آزمایش',
            'currency' => '',
            'timezone' => '',
            'booking_confirmation_mode' => '',
        ])->assertRedirect();

        $this->assertSame('کلینیک آزمایش', ClinicSetting::getValue(ClinicSetting::CLINIC_NAME));
        $this->assertNull(ClinicSetting::getValue(ClinicSetting::CURRENCY));
        $this->assertNull(ClinicSetting::getValue(ClinicSetting::TIMEZONE));
        $this->assertNull(ClinicSetting::getValue(ClinicSetting::BOOKING_CONFIRMATION_MODE));
    }

    public function test_receptionist_cannot_create_a_chair(): void
    {
        $this->signIn(UserRole::Receptionist);

        $this->post(route('chairs.store'), [
            'code' => 'U9',
            'name' => 'غیرمجاز',
            'is_active' => '1',
        ])->assertForbidden();
    }
}
