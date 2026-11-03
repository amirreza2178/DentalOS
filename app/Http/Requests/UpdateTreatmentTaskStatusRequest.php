<?php

namespace App\Http\Requests;

use App\Enums\TreatmentTaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTreatmentTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');

        if ($task === null || $this->user() === null) {
            return false;
        }

        $status = TreatmentTaskStatus::tryFrom((string) $this->input('status'));

        if ($status === TreatmentTaskStatus::Cancelled) {
            return $this->user()->can('cancel', $task);
        }

        if ($status === TreatmentTaskStatus::Completed) {
            return $this->user()->can('complete', $task);
        }

        return $this->user()->can('updateStatus', $task);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TreatmentTaskStatus::class)],
            'completion_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
