<?php

namespace Tests\Feature\Clinic;

use App\Enums\UserRole;
use App\Models\ClinicSetting;
use App\Support\ClinicIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_uses_central_doctor_display_name_without_hardcoded_controllers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(ClinicIdentity::DEFAULT_DOCTOR_DISPLAY_NAME, false)
            ->assertSee(ClinicIdentity::DEFAULT_DOCTOR_TITLE, false)
            ->assertSee(ClinicIdentity::DEFAULT_CLINIC_DISPLAY_NAME, false)
            ->assertSee('تأیید', false);
    }

    public function test_admin_can_override_identity_centrally(): void
    {
        $this->signIn(UserRole::Admin);

        $this->put(route('settings.update'), [
            'clinic_name' => 'کلینیک آزمایش هویت',
            'doctor_display_name' => 'دکتر هومان شفاعی',
            'doctor_title' => 'متخصص ارتودنسی',
            'clinic_intro' => 'متن معرفی تأییدشده آزمایشی',
            'booking_display_note' => 'درخواست آنلاین نیاز به تأیید پذیرش دارد.',
            'primary_color' => '#0B1220',
            'accent_color' => '#55E6D0',
        ])->assertRedirect(route('settings.edit'));

        $this->assertSame('کلینیک آزمایش هویت', ClinicSetting::getValue(ClinicSetting::CLINIC_NAME));
        $this->assertSame('دکتر هومان شفاعی', ClinicSetting::getValue(ClinicSetting::DOCTOR_DISPLAY_NAME));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('کلینیک آزمایش هویت', false)
            ->assertSee('دکتر هومان شفاعی', false)
            ->assertSee('درخواست آنلاین نیاز به تأیید پذیرش دارد.', false);
    }

    public function test_identity_defaults_remain_safe_when_settings_empty(): void
    {
        $identity = app(ClinicIdentity::class);

        $this->assertSame(ClinicIdentity::DEFAULT_DOCTOR_DISPLAY_NAME, $identity->doctorDisplayName());
        $this->assertSame(ClinicIdentity::DEFAULT_DOCTOR_TITLE, $identity->doctorTitle());
        $this->assertFalse($identity->hasLogo());
        $this->assertFalse($identity->hasDoctorPortrait());
    }
}
