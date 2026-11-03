<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFollowUpOperationalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $followUp = $this->route('followUp');

        return $followUp !== null && ($this->user()?->can('updateOperational', $followUp) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'outcome_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
