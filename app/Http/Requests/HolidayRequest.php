<?php

namespace App\Http\Requests;

use App\Models\Holiday;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class HolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Holiday::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'holiday_date' => ['required', 'string', 'max:20'],
            'holiday_date_gregorian' => ['required', 'date_format:Y-m-d', Rule::unique('holidays', 'holiday_date')],
            'title' => ['required', 'string', 'max:150'],
            'is_closed' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        try {
            $gregorian = app(JalaliCalendar::class)->parseToGregorianDate($this->input('holiday_date'));
        } catch (InvalidArgumentException) {
            $gregorian = null;
        }

        $this->merge([
            'holiday_date_gregorian' => $gregorian,
            'is_closed' => $this->boolean('is_closed'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'holiday_date.required' => 'تاریخ تعطیلی را وارد کنید.',
            'holiday_date_gregorian.required' => 'تاریخ را با قالب جلالی صحیح وارد کنید.',
            'holiday_date_gregorian.unique' => 'برای این تاریخ قبلاً تعطیلی ثبت شده است.',
            'title.required' => 'عنوان تعطیلی را وارد کنید.',
        ];
    }
}
