<?php

namespace App\Http\Requests;

use App\Enums\BookingConfirmationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClinicSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', \App\Models\ClinicSetting::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clinic_name' => ['nullable', 'string', 'max:150'],
            'doctor_display_name' => ['nullable', 'string', 'max:120'],
            'doctor_title' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'clinic_address' => ['nullable', 'string', 'max:500'],
            'clinic_intro' => ['nullable', 'string', 'max:1000'],
            'primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo_path' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\\/.-]+$/'],
            'doctor_portrait_path' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_\\/.-]+$/'],
            'booking_display_note' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['nullable', 'string', 'max:64', 'timezone:all'],
            'currency' => ['nullable', 'string', 'max:12'],
            'booking_confirmation_mode' => ['nullable', Rule::enum(BookingConfirmationMode::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $keys = [
            'clinic_name',
            'doctor_display_name',
            'doctor_title',
            'contact_phone',
            'clinic_address',
            'clinic_intro',
            'primary_color',
            'accent_color',
            'logo_path',
            'doctor_portrait_path',
            'booking_display_note',
            'timezone',
            'currency',
            'booking_confirmation_mode',
        ];

        $merged = [];
        foreach ($keys as $key) {
            $merged[$key] = $this->emptyToNull($key);
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'timezone.timezone' => 'منطقه زمانی شناخته‌شده نیست. مقداری مانند Asia/Tehran را فقط پس از تأیید کلینیک وارد کنید.',
            'primary_color.regex' => 'رنگ اصلی باید به صورت هگز شش‌رقمی مانند #0B1220 باشد.',
            'accent_color.regex' => 'رنگ تأکید باید به صورت هگز شش‌رقمی مانند #55E6D0 باشد.',
            'logo_path.regex' => 'مسیر لوگو فقط می‌تواند مسیر نسبی امن داخل public باشد.',
            'doctor_portrait_path.regex' => 'مسیر تصویر فقط می‌تواند مسیر نسبی امن داخل public باشد.',
        ];
    }

    private function emptyToNull(string $key): ?string
    {
        $value = $this->input($key);

        return filled($value) ? (string) $value : null;
    }
}
