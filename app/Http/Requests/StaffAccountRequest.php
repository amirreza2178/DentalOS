<?php

namespace App\Http\Requests;

use App\Enums\Qualification;
use App\Models\User;
use App\Support\StaffDirectory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $account = $this->route('account');

        if ($account instanceof User) {
            return $this->user()?->can('update', $account) ?? false;
        }

        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $role = StaffDirectory::role((string) $this->route('directory'));
        $accountId = $this->route('account')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($accountId)],
            'mobile' => ['nullable', 'string', 'max:32', Rule::unique('users', 'mobile')->ignore($accountId)],
            'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['required', 'boolean'],
            'qualification' => $role->requiresStaffProfile()
                ? ['required', Rule::enum(Qualification::class)]
                : ['nullable'],
            'title' => ['nullable', 'string', 'max:150'],
            'professional_summary' => ['nullable', 'string', 'max:2000'],
            'can_take_clinical_notes' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => filled($this->input('mobile')) ? $this->input('mobile') : null,
            'title' => filled($this->input('title')) ? $this->input('title') : null,
            'professional_summary' => filled($this->input('professional_summary')) ? $this->input('professional_summary') : null,
            'is_active' => $this->boolean('is_active'),
            'can_take_clinical_notes' => $this->boolean('can_take_clinical_notes'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام را وارد کنید.',
            'email.required' => 'ایمیل را وارد کنید.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'mobile.unique' => 'این شماره برای حساب کاربری دیگری ثبت شده است.',
            'password.required' => 'رمز عبور را وارد کنید.',
            'password.min' => 'رمز عبور باید حداقل ۸ نویسه باشد.',
            'password.confirmed' => 'تکرار رمز عبور یکسان نیست.',
            'qualification.required' => 'سطح صلاحیت را صریحاً انتخاب کنید.',
        ];
    }
}
