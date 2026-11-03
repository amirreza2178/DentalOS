<?php

namespace App\Http\Requests;

use App\Enums\TreatmentPlanStatus;
use App\Models\TreatmentPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TreatmentPlan::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clinical_summary' => ['required', 'string', 'min:3', 'max:5000'],
            'operational_summary' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::enum(TreatmentPlanStatus::class)],
            'patient_visit_id' => ['nullable', 'integer', 'exists:patient_visits,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'clinical_summary.required' => 'خلاصه تصمیم بالینی را وارد کنید.',
            'clinical_summary.min' => 'خلاصه تصمیم بالینی خیلی کوتاه است.',
        ];
    }
}
