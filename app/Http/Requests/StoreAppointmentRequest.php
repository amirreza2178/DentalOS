<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use App\Support\ClockInput;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'treatment_service_id' => ['required', 'integer', 'exists:treatment_services,id'],
            'appointment_date' => ['required', 'string', 'max:20'],
            'appointment_date_gregorian' => ['required', 'date_format:Y-m-d'],
            'appointment_time' => ['required', 'date_format:H:i:s'],
            'specialist_id' => ['nullable', 'integer', 'exists:users,id'],
            'clinical_staff_ids' => ['array'],
            'clinical_staff_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'chair_id' => ['nullable', 'integer', 'exists:chairs,id'],
            'patient_notes' => ['nullable', 'string', 'max:2000'],
            'follow_up_id' => ['nullable', 'integer', 'exists:follow_ups,id'],
            'follow_up_authorization' => ['nullable', 'in:receptionist,patient'],
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

        $staffIds = array_values(array_filter(
            array_map(static fn ($id): int => (int) $id, (array) $this->input('clinical_staff_ids', [])),
            static fn (int $id): bool => $id > 0,
        ));

        $this->merge([
            'appointment_date_gregorian' => $gregorian,
            'appointment_time' => ClockInput::normalize($this->input('appointment_time')),
            'specialist_id' => filled($this->input('specialist_id')) ? $this->input('specialist_id') : null,
            'chair_id' => filled($this->input('chair_id')) ? $this->input('chair_id') : null,
            'clinical_staff_ids' => $staffIds,
            'patient_notes' => filled($this->input('patient_notes')) ? $this->input('patient_notes') : null,
            'follow_up_id' => filled($this->input('follow_up_id')) ? $this->input('follow_up_id') : null,
            'follow_up_authorization' => filled($this->input('follow_up_authorization'))
                ? $this->input('follow_up_authorization')
                : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'patient_id.required' => 'بیمار را انتخاب کنید.',
            'treatment_service_id.required' => 'خدمت را انتخاب کنید.',
            'appointment_date.required' => 'تاریخ نوبت را وارد کنید.',
            'appointment_date_gregorian.required' => 'تاریخ را با قالب جلالی صحیح وارد کنید.',
            'appointment_time.required' => 'ساعت نوبت را وارد کنید.',
            'appointment_time.date_format' => 'ساعت را با قالب ساعت:دقیقه وارد کنید.',
            'clinical_staff_ids.*.distinct' => 'یک نفر را نمی‌توان چند بار برای همین نوبت انتخاب کرد.',
        ];
    }
}
