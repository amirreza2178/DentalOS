<?php

namespace App\Support;

use App\Models\ClinicSetting;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Single clinic branding source. DB overrides optional; approved defaults when empty.
 */
final class ClinicIdentity
{
    public const DEFAULT_CLINIC_DISPLAY_NAME = 'کلینیک ارتودنسی دکتر هومان شفاعی';

    public const DEFAULT_PUBLIC_NAV_BRAND = 'کلینیک ارتودنسی';

    public const DEFAULT_DOCTOR_DISPLAY_NAME = 'دکتر هومان شفاعی';

    public const DEFAULT_DOCTOR_TITLE = 'متخصص ارتودنسی';

    public const DEFAULT_PRIMARY_COLOR = '#0B1220';

    public const DEFAULT_ACCENT_COLOR = '#55E6D0';

    public const DEFAULT_LIGHT_COLOR = '#F5F7FA';

    public const DEFAULT_INTRODUCTION = 'درخواست نوبت آنلاین برای بررسی پذیرش ثبت می‌شود و به‌خودی‌خود نوبت قطعی نیست.';

    public const DEFAULT_BOOKING_CLARIFICATION = 'ثبت درخواست آنلاین فقط یک درخواست است. پذیرش کلینیک آن را بررسی می‌کند و در صورت تأیید، نوبت قطعی می‌شود. تا قبل از تأیید، نوبت نهایی محسوب نمی‌شود.';

    public function clinicDisplayName(): string
    {
        return $this->setting(ClinicSetting::CLINIC_NAME, self::DEFAULT_CLINIC_DISPLAY_NAME);
    }

    /**
     * Compact public chrome brand. Avoids repeating the doctor name already shown in hero.
     */
    public function publicNavBrand(): string
    {
        $custom = $this->rawSetting(ClinicSetting::CLINIC_NAME);

        if ($custom !== null && ! str_contains($custom, $this->doctorDisplayName())) {
            return $custom;
        }

        return self::DEFAULT_PUBLIC_NAV_BRAND;
    }

    public function doctorDisplayName(): string
    {
        return $this->setting(ClinicSetting::DOCTOR_DISPLAY_NAME, self::DEFAULT_DOCTOR_DISPLAY_NAME);
    }

    public function doctorTitle(): string
    {
        return $this->setting(ClinicSetting::DOCTOR_TITLE, self::DEFAULT_DOCTOR_TITLE);
    }

    public function contactPhone(): ?string
    {
        return $this->rawSetting(ClinicSetting::CONTACT_PHONE);
    }

    public function address(): ?string
    {
        return $this->rawSetting(ClinicSetting::CLINIC_ADDRESS);
    }

    public function introduction(): string
    {
        return $this->setting(ClinicSetting::CLINIC_INTRO, self::DEFAULT_INTRODUCTION);
    }

    public function bookingClarification(): string
    {
        return $this->setting(ClinicSetting::BOOKING_DISPLAY_NOTE, self::DEFAULT_BOOKING_CLARIFICATION);
    }

    public function primaryColor(): string
    {
        return $this->safeHex($this->rawSetting(ClinicSetting::PRIMARY_COLOR), self::DEFAULT_PRIMARY_COLOR);
    }

    public function accentColor(): string
    {
        return $this->safeHex($this->rawSetting(ClinicSetting::ACCENT_COLOR), self::DEFAULT_ACCENT_COLOR);
    }

    public function lightColor(): string
    {
        return self::DEFAULT_LIGHT_COLOR;
    }

    public function logoPath(): ?string
    {
        return $this->publicAssetPath($this->rawSetting(ClinicSetting::LOGO_PATH));
    }

    public function doctorPortraitPath(): ?string
    {
        return $this->publicAssetPath($this->rawSetting(ClinicSetting::DOCTOR_PORTRAIT_PATH));
    }

    public function hasLogo(): bool
    {
        return $this->logoPath() !== null;
    }

    public function hasDoctorPortrait(): bool
    {
        return $this->doctorPortraitPath() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewData(): array
    {
        return [
            'clinicDisplayName' => $this->clinicDisplayName(),
            'publicNavBrand' => $this->publicNavBrand(),
            'doctorDisplayName' => $this->doctorDisplayName(),
            'doctorTitle' => $this->doctorTitle(),
            'contactPhone' => $this->contactPhone(),
            'address' => $this->address(),
            'introduction' => $this->introduction(),
            'bookingClarification' => $this->bookingClarification(),
            'primaryColor' => $this->primaryColor(),
            'accentColor' => $this->accentColor(),
            'lightColor' => $this->lightColor(),
            'logoPath' => $this->logoPath(),
            'doctorPortraitPath' => $this->doctorPortraitPath(),
            'hasLogo' => $this->hasLogo(),
            'hasDoctorPortrait' => $this->hasDoctorPortrait(),
        ];
    }

    private function setting(string $key, string $default): string
    {
        $value = $this->rawSetting($key);

        return $value === null ? $default : $value;
    }

    private function rawSetting(string $key): ?string
    {
        try {
            if (! Schema::hasTable('clinic_settings')) {
                return null;
            }

            return ClinicSetting::getValue($key);
        } catch (Throwable) {
            return null;
        }
    }

    private function safeHex(?string $value, string $fallback): string
    {
        if ($value === null) {
            return $fallback;
        }

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : $fallback;
    }

    private function publicAssetPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, ':')) {
            return null;
        }

        $full = public_path($path);

        return is_file($full) ? $path : null;
    }
}
