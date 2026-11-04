<?php

namespace App\Enums;

enum CancellationReason: string
{
    case PatientRequest = 'patient_request';
    case ScheduleConflict = 'schedule_conflict';
    case ClinicUnavailable = 'clinic_unavailable';
    case BookedInError = 'booked_in_error';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PatientRequest => 'درخواست بیمار / همراه',
            self::ScheduleConflict => 'تداخل برنامه یا جابه‌جایی ناموفق',
            self::ClinicUnavailable => 'عدم امکان کلینیک (شیفت/یونیت/تعطیلی)',
            self::BookedInError => 'ثبت اشتباه نوبت',
            self::Other => 'سایر (با توضیح کوتاه)',
        };
    }

    /**
     * Encode into appointments.decision_reason without a new column.
     * Prefix keeps staff-cancel audit distinct from free-text online reject reasons.
     */
    public function encode(?string $note = null): string
    {
        $normalized = is_string($note) ? trim(preg_replace('/\s+/u', ' ', $note) ?? '') : '';
        $normalized = mb_substr($normalized, 0, 200);

        if ($normalized === '') {
            return 'cancel:'.$this->value;
        }

        return 'cancel:'.$this->value.'|'.$normalized;
    }

    /**
     * @return array{reason: self, note: string}|null
     */
    public static function tryDecode(?string $stored): ?array
    {
        if (! is_string($stored) || ! str_starts_with($stored, 'cancel:')) {
            return null;
        }

        $body = substr($stored, strlen('cancel:'));
        $parts = explode('|', $body, 2);
        $reason = self::tryFrom($parts[0] ?? '');

        if ($reason === null) {
            return null;
        }

        return [
            'reason' => $reason,
            'note' => trim((string) ($parts[1] ?? '')),
        ];
    }
}
