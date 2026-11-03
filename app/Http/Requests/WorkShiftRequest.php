<?php

namespace App\Http\Requests;

use App\Enums\Weekday;
use App\Models\WorkShift;
use App\Support\ClockInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WorkShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $shift = $this->route('shift');

        if ($shift instanceof WorkShift) {
            return $this->user()?->can('update', $shift) ?? false;
        }

        return $this->user()?->can('create', WorkShift::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'weekday' => ['required', 'integer', Rule::in(array_map(fn (Weekday $day): int => $day->value, Weekday::cases()))],
            'starts_at' => ['required', 'date_format:H:i:s'],
            'ends_at' => ['required', 'date_format:H:i:s'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'weekday' => is_numeric($this->input('weekday')) ? (int) $this->input('weekday') : $this->input('weekday'),
            'starts_at' => ClockInput::normalize($this->input('starts_at')),
            'ends_at' => ClockInput::normalize($this->input('ends_at')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $start = $this->input('starts_at');
            $end = $this->input('ends_at');

            if (! is_string($start) || ! is_string($end) || $start >= $end) {
                $validator->errors()->add('ends_at', 'ساعت پایان باید بعد از ساعت شروع باشد.');

                return;
            }

            $shift = $this->route('shift');
            $overlap = WorkShift::query()
                ->where('weekday', $this->input('weekday'))
                ->where('is_active', true)
                ->when($shift instanceof WorkShift, fn ($query) => $query->whereKeyNot($shift->id))
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->exists();

            if ($overlap && $this->boolean('is_active')) {
                $validator->errors()->add('starts_at', 'این شیفت با یک شیفت فعال دیگر در همین روز تداخل دارد.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام شیفت را وارد کنید.',
            'weekday.required' => 'روز هفته را انتخاب کنید.',
            'starts_at.required' => 'ساعت شروع را وارد کنید.',
            'ends_at.required' => 'ساعت پایان را وارد کنید.',
        ];
    }
}
