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
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'timezone' => ['nullable', 'string', 'max:64', 'timezone:all'],
            'currency' => ['nullable', 'string', 'max:12'],
            'booking_confirmation_mode' => ['nullable', Rule::enum(BookingConfirmationMode::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'clinic_name' => $this->emptyToNull('clinic_name'),
            'contact_phone' => $this->emptyToNull('contact_phone'),
            'timezone' => $this->emptyToNull('timezone'),
            'currency' => $this->emptyToNull('currency'),
            'booking_confirmation_mode' => $this->emptyToNull('booking_confirmation_mode'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'timezone.timezone' => 'منطقه زمانی شناخته‌شده نیست. مقداری مانند Asia/Tehran را فقط پس از تأیید کلینیک وارد کنید.',
        ];
    }

    private function emptyToNull(string $key): ?string
    {
        $value = $this->input($key);

        return filled($value) ? (string) $value : null;
    }
}
