<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class RejectOnlineBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Appointment $appointment */
        $appointment = $this->route('appointment');

        return $this->user()?->can('decideOnline', $appointment) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision_reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input('decision_reason');
        if (is_string($reason)) {
            $reason = trim(strip_tags($reason));
            $reason = $reason === '' ? null : $reason;
        } else {
            $reason = null;
        }

        $this->merge(['decision_reason' => $reason]);
    }
}
