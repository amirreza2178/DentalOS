<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Enums\PatientStatus;
use App\Models\Patient;
use App\Support\JalaliCalendar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class PatientFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $patient = $this->route('patient');

        if ($patient instanceof Patient) {
            return $this->user()?->can('update', $patient) ?? false;
        }

        return $this->user()?->can('create', Patient::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $patientId = $this->route('patient')?->id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:32', Rule::unique('patients', 'code')->ignore($patientId)],
            'national_id' => ['nullable', 'string', 'max:20', Rule::unique('patients', 'national_id')->ignore($patientId)],
            'mobile' => ['nullable', 'string', 'max:32'],
            'alt_phone' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! filled($value)) {
                    return;
                }

                try {
                    app(JalaliCalendar::class)->parseToGregorianDate((string) $value);
                } catch (InvalidArgumentException) {
                    $fail('تاریخ تولد معتبر نیست. تاریخ جلالی را به شکل ۱۴۰۴/۰۱/۰۱ وارد کنید.');
                }
            }],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'status' => ['required', Rule::enum(PatientStatus::class)],
            'notes_admin' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->emptyToNull('code'),
            'national_id' => $this->emptyToNull('national_id'),
            'mobile' => $this->emptyToNull('mobile'),
            'alt_phone' => $this->emptyToNull('alt_phone'),
            'gender' => $this->emptyToNull('gender'),
            'notes_admin' => $this->emptyToNull('notes_admin'),
            'birth_date' => $this->emptyToNull('birth_date'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): mixed
    {
        $validated = parent::validated($key, $default);

        if ($key !== null || ! is_array($validated)) {
            return $validated;
        }

        $validated['birth_date'] = app(JalaliCalendar::class)->parseToGregorianDate($validated['birth_date'] ?? null);

        return $validated;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'نام را وارد کنید.',
            'last_name.required' => 'نام خانوادگی را وارد کنید.',
            'code.unique' => 'این شناسه پرونده قبلاً ثبت شده است.',
            'national_id.unique' => 'این کد ملی قبلاً ثبت شده است.',
            'birth_date.date' => 'تاریخ تولد معتبر نیست. تاریخ جلالی را به شکل ۱۴۰۴/۰۱/۰۱ وارد کنید.',
            'status.required' => 'وضعیت را انتخاب کنید.',
        ];
    }

    private function emptyToNull(string $key): ?string
    {
        $value = $this->input($key);

        return filled($value) ? (string) $value : null;
    }
}
