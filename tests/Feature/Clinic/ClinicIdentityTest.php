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

    public function test_public_home_promotes_doctor_name_once_in_hero_without_redundant_repeats(): void
    {
        $response = $this->get(route('home'))
            ->assertOk()
            ->assertSee(ClinicIdentity::DEFAULT_DOCTOR_DISPLAY_NAME, false)
            ->assertSee(ClinicIdentity::DEFAULT_DOCTOR_TITLE, false)
            ->assertSee(ClinicIdentity::DEFAULT_PUBLIC_NAV_BRAND, false)
            ->assertSee('شروع درخواست نوبت', false)
            ->assertSee('مسیر درخواست نوبت', false)
            ->assertSee('ورود کارکنان', false);

        $html = $response->getContent();
        $doctorMentions = substr_count($html, ClinicIdentity::DEFAULT_DOCTOR_DISPLAY_NAME);
        // Title + hero H1 is enough; body copy must not keep restating the name.
        $this->assertLessThanOrEqual(2, $doctorMentions, 'Doctor display name should not flood the homepage.');
        $this->assertGreaterThanOrEqual(1, $doctorMentions);
        $this->assertStringNotContainsString(ClinicIdentity::DEFAULT_CLINIC_DISPLAY_NAME, $html);
        $this->assertSame(1, substr_count($html, 'شروع درخواست نوبت'));
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
        $this->assertSame(ClinicIdentity::DEFAULT_PUBLIC_NAV_BRAND, $identity->publicNavBrand());
        $this->assertFalse($identity->hasLogo());
        $this->assertFalse($identity->hasDoctorPortrait());
    }
}
