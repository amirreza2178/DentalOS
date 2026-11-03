<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClinicSetting extends Model
{
    public const CLINIC_NAME = 'clinic_name';

    public const CONTACT_PHONE = 'contact_phone';

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
            self::CONTACT_PHONE,
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
