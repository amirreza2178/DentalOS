<?php

namespace App\Http\Requests;

use App\Models\Chair;
use App\Support\CapabilityList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChairRequest extends FormRequest
{
    public function authorize(): bool
    {
        $chair = $this->route('chair');

        if ($chair instanceof Chair) {
            return $this->user()?->can('update', $chair) ?? false;
        }

        return $this->user()?->can('create', Chair::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $chairId = $this->route('chair')?->id;

        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('chairs', 'code')->ignore($chairId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'capabilities' => ['nullable', 'array'],
            'capabilities.*' => ['string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => filled($this->input('description')) ? $this->input('description') : null,
            'capabilities' => CapabilityList::parse($this->input('capabilities')),
            'sort_order' => $this->input('sort_order') === null || $this->input('sort_order') === '' ? 0 : $this->input('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'کد یونیت را وارد کنید.',
            'code.unique' => 'این کد یونیت قبلاً ثبت شده است.',
            'name.required' => 'نام یونیت را وارد کنید.',
        ];
    }
}
