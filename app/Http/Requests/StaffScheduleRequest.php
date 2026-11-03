<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\StaffSchedule;
use App\Models\User;
use App\Support\ClockInput;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class StaffScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StaffSchedule::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'kind' => ['required', Rule::in(['recurring', 'date', 'day_off'])],
            'work_shift_id' => ['nullable', 'integer', 'exists:work_shifts,id'],
            'schedule_date' => ['nullable', 'string', 'max:20'],
            'schedule_date_gregorian' => ['nullable', 'date_format:Y-m-d'],
            'starts_at' => ['nullable', 'date_format:H:i:s'],
            'ends_at' => ['nullable', 'date_format:H:i:s'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        try {
            $gregorian = app(JalaliCalendar::class)->parseToGregorianDate($this->input('schedule_date'));
        } catch (InvalidArgumentException) {
            $gregorian = null;
        }

        $this->merge([
            'work_shift_id' => filled($this->input('work_shift_id')) ? $this->input('work_shift_id') : null,
            'schedule_date_gregorian' => $gregorian,
            'starts_at' => ClockInput::normalize($this->input('starts_at')),
            'ends_at' => ClockInput::normalize($this->input('ends_at')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = User::query()->with('staffProfile')->find($this->input('user_id'));

            if ($user === null || ! $user->is_active || ! $user->hasRole(UserRole::Specialist, UserRole::ClinicalStaff) || $user->staffProfile === null || ! $user->staffProfile->is_active) {
                $validator->errors()->add('user_id', 'فقط متخصص یا کادر بالینی فعال را می‌توان در برنامه گذاشت.');
            }

            $kind = $this->input('kind');
            $start = $this->input('starts_at');
            $end = $this->input('ends_at');

            if ($kind === 'recurring' && ! filled($this->input('work_shift_id'))) {
                $validator->errors()->add('work_shift_id', 'برای برنامه تکرارشونده شیفت را انتخاب کنید.');
            }

            if (in_array($kind, ['date', 'day_off'], true) && ! filled($this->input('schedule_date_gregorian'))) {
                $validator->errors()->add('schedule_date', 'تاریخ را با قالب جلالی صحیح وارد کنید.');
            }

            if ($kind === 'date' && (! is_string($start) || ! is_string($end) || $start >= $end)) {
                $validator->errors()->add('ends_at', 'بازه تاریخ خاص باید ساعت شروع و پایان معتبر داشته باشد.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'کارکنان را انتخاب کنید.',
            'kind.required' => 'نوع برنامه را انتخاب کنید.',
        ];
    }
}
