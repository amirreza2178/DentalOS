<?php

namespace App\Http\Requests;

use App\Enums\CancellationReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');

        return $appointment !== null && $this->user()?->can('cancel', $appointment) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', Rule::enum(CancellationReason::class)],
            'cancellation_note' => ['nullable', 'string', 'max:200'],
            'return_to' => ['nullable', 'string', 'in:reception.day'],
            'return_date' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cancellation_reason.required' => 'برای لغو نوبت، دلیل را انتخاب کنید.',
            'cancellation_reason.*' => 'دلیل لغو معتبر نیست.',
            'cancellation_note.max' => 'توضیح کوتاه نباید بیشتر از ۲۰۰ نویسه باشد.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('cancellation_note')) {
            $note = trim((string) $this->input('cancellation_note'));
            $this->merge([
                'cancellation_note' => $note === '' ? null : $note,
            ]);
        }
    }
}
