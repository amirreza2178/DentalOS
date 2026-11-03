<?php

namespace App\Http\Requests;

use App\Models\FollowUp;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class StoreFollowUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FollowUp::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'proposed_window_start' => ['required', 'string', 'max:20'],
            'proposed_window_end' => ['required', 'string', 'max:20'],
            'proposed_window_start_gregorian' => ['required', 'date_format:Y-m-d'],
            'proposed_window_end_gregorian' => ['required', 'date_format:Y-m-d', 'after_or_equal:proposed_window_start_gregorian'],
            'treatment_service_id' => ['nullable', 'integer', 'exists:treatment_services,id'],
            'operational_note' => ['nullable', 'string', 'max:2000'],
            'patient_visit_id' => ['nullable', 'integer', 'exists:patient_visits,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $calendar = app(JalaliCalendar::class);
        $start = null;
        $end = null;

        try {
            $start = $calendar->parseToGregorianDate($this->input('proposed_window_start'));
        } catch (InvalidArgumentException) {
            $start = null;
        }

        try {
            $end = $calendar->parseToGregorianDate($this->input('proposed_window_end'));
        } catch (InvalidArgumentException) {
            $end = null;
        }

        $this->merge([
            'proposed_window_start_gregorian' => $start,
            'proposed_window_end_gregorian' => $end,
            'treatment_service_id' => filled($this->input('treatment_service_id')) ? $this->input('treatment_service_id') : null,
            'patient_visit_id' => filled($this->input('patient_visit_id')) ? $this->input('patient_visit_id') : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'proposed_window_start_gregorian.required' => 'شروع بازه را با تاریخ جلالی صحیح وارد کنید.',
            'proposed_window_end_gregorian.required' => 'پایان بازه را با تاریخ جلالی صحیح وارد کنید.',
            'proposed_window_end_gregorian.after_or_equal' => 'پایان بازه نمی‌تواند قبل از شروع باشد.',
        ];
    }
}
