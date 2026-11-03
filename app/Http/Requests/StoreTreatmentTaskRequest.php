<?php

namespace App\Http\Requests;

use App\Enums\Qualification;
use App\Models\TreatmentTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTreatmentTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TreatmentTask::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:2', 'max:200'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'required_qualification' => ['required', Rule::enum(Qualification::class)],
            'assignee_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'عنوان وظیفه را وارد کنید.',
            'required_qualification.required' => 'صلاحیت لازم را مشخص کنید.',
        ];
    }
}
