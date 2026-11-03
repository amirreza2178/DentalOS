<?php

namespace App\Http\Requests;

use App\Support\ClockInput;
use App\Support\Digits;
use App\Support\JalaliCalendar;
use App\Support\PhoneNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class StoreOnlineBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'treatment_service_id' => ['required', 'integer', 'exists:treatment_services,id'],
            'appointment_date' => ['required', 'string', 'max:20'],
            'appointment_date_gregorian' => ['required', 'date_format:Y-m-d'],
            'appointment_time' => ['required', 'date_format:H:i:s'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'mobile' => ['required', 'string', 'max:20'],
            'patient_notes' => ['nullable', 'string', 'max:300'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $gregorian = null;

        try {
            $gregorian = app(JalaliCalendar::class)->parseToGregorianDate($this->input('appointment_date'));
        } catch (InvalidArgumentException) {
            $gregorian = null;
        }

        $notes = $this->sanitizePlainText($this->input('patient_notes'), 300);

        $this->merge([
            'appointment_date_gregorian' => $gregorian,
            'appointment_time' => ClockInput::normalize($this->input('appointment_time')),
            'first_name' => $this->sanitizePlainText($this->input('first_name'), 80),
            'last_name' => $this->sanitizePlainText($this->input('last_name'), 80),
            'mobile' => is_string($this->input('mobile')) ? trim(Digits::toAscii($this->input('mobile'))) : null,
            'patient_notes' => $notes,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $normalized = app(PhoneNormalizer::class)->normalize($this->input('mobile'));

            if ($normalized === null || ! preg_match('/^09\d{9}$/', $normalized)) {
                $validator->errors()->add('mobile', 'شماره موبایل را به‌درستی وارد کنید.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'treatment_service_id.required' => 'خدمت را انتخاب کنید.',
            'appointment_date.required' => 'تاریخ را انتخاب کنید.',
            'appointment_date_gregorian.required' => 'تاریخ را با قالب جلالی صحیح وارد کنید.',
            'appointment_time.required' => 'ساعت را انتخاب کنید.',
            'first_name.required' => 'نام را وارد کنید.',
            'last_name.required' => 'نام خانوادگی را وارد کنید.',
            'mobile.required' => 'شماره موبایل را وارد کنید.',
            'idempotency_key.required' => 'لطفاً فرم را دوباره ارسال کنید.',
            'idempotency_key.uuid' => 'لطفاً فرم را دوباره ارسال کنید.',
        ];
    }

    private function sanitizePlainText(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $clean = preg_replace('/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/is', '', $value) ?? '';
        $clean = strip_tags($clean);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? '');

        if ($clean === '') {
            return null;
        }

        return mb_substr($clean, 0, $max);
    }
}
