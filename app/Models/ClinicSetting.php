<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicSetting extends Model
{
    public const CLINIC_NAME = 'clinic_name';

    public const DOCTOR_DISPLAY_NAME = 'doctor_display_name';

    public const DOCTOR_TITLE = 'doctor_title';

    public const CONTACT_PHONE = 'contact_phone';

    public const CLINIC_ADDRESS = 'clinic_address';

    public const CLINIC_INTRO = 'clinic_intro';

    public const PRIMARY_COLOR = 'primary_color';

    public const ACCENT_COLOR = 'accent_color';

    public const LOGO_PATH = 'logo_path';

    public const DOCTOR_PORTRAIT_PATH = 'doctor_portrait_path';

    public const BOOKING_DISPLAY_NOTE = 'booking_display_note';

    public const TIMEZONE = 'timezone';

    public const CURRENCY = 'currency';

    public const BOOKING_CONFIRMATION_MODE = 'booking_confirmation_mode';

    protected $fillable = ['key', 'value'];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::CLINIC_NAME,
            self::DOCTOR_DISPLAY_NAME,
            self::DOCTOR_TITLE,
            self::CONTACT_PHONE,
            self::CLINIC_ADDRESS,
            self::CLINIC_INTRO,
            self::PRIMARY_COLOR,
            self::ACCENT_COLOR,
            self::LOGO_PATH,
            self::DOCTOR_PORTRAIT_PATH,
            self::BOOKING_DISPLAY_NOTE,
            self::TIMEZONE,
            self::CURRENCY,
            self::BOOKING_CONFIRMATION_MODE,
        ];
    }

    public static function getValue(string $key): ?string
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value === null || $value === '' ? null : $value;
    }

    public static function putValue(string $key, ?string $value): void
    {
        if (! in_array($key, self::keys(), true)) {
            throw new \InvalidArgumentException('Unknown clinic setting.');
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null || $value === '' ? null : $value],
        );
    }
}
