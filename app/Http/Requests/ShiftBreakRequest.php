<?php

namespace App\Http\Requests;

use App\Models\WorkShift;
use App\Support\ClockInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ShiftBreakRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shift = $this->route('shift');

        return $shift instanceof WorkShift
            && ($this->user()?->can('update', $shift) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date_format:H:i:s'],
            'ends_at' => ['required', 'date_format:H:i:s'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => filled($this->input('name')) ? $this->input('name') : null,
            'starts_at' => ClockInput::normalize($this->input('starts_at')),
            'ends_at' => ClockInput::normalize($this->input('ends_at')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $shift = $this->route('shift');
            $start = $this->input('starts_at');
            $end = $this->input('ends_at');

            if (! $shift instanceof WorkShift || ! is_string($start) || ! is_string($end)) {
                return;
            }

            if ($start >= $end) {
                $validator->errors()->add('ends_at', 'ساعت پایان استراحت باید بعد از شروع آن باشد.');

                return;
            }

            $shiftStart = ClockInput::normalize((string) $shift->starts_at);
            $shiftEnd = ClockInput::normalize((string) $shift->ends_at);

            if ($start < $shiftStart || $end > $shiftEnd) {
                $validator->errors()->add('starts_at', 'استراحت باید داخل ساعت شیفت باشد.');
            }

            $overlap = $shift->breaks()
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->exists();

            if ($overlap) {
                $validator->errors()->add('starts_at', 'این استراحت با استراحت دیگری در همین شیفت تداخل دارد.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'starts_at.required' => 'ساعت شروع استراحت را وارد کنید.',
            'ends_at.required' => 'ساعت پایان استراحت را وارد کنید.',
        ];
    }
}
