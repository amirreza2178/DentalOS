<?php

namespace App\Http\Requests;

use App\Enums\TreatmentPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('plan');

        return $plan !== null && ($this->user()?->can('update', $plan) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clinical_summary' => ['required', 'string', 'min:3', 'max:5000'],
            'operational_summary' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(TreatmentPlanStatus::class)],
        ];
    }
}
