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
            'decision_reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decision_reason.required' => 'برای رد درخواست آنلاین، دلیل را بنویسید.',
            'decision_reason.min' => 'دلیل رد باید حداقل ۳ نویسه باشد.',
            'decision_reason.max' => 'دلیل رد نباید بیشتر از ۵۰۰ نویسه باشد.',
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
