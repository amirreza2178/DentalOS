<?php

namespace App\Http\Requests;

use App\Enums\BookingConfirmationMode;
use App\Models\TreatmentService;
use App\Support\CapabilityList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TreatmentServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $service = $this->route('service');

        if ($service instanceof TreatmentService) {
            return $this->user()?->can('update', $service) ?? false;
        }

        return $this->user()?->can('create', TreatmentService::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $serviceId = $this->route('service')?->id;

        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('treatment_services', 'code')->ignore($serviceId)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'default_duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'buffer_minutes' => ['required', 'integer', 'min:0', 'max:120'],
            'requires_specialist' => ['required', 'boolean'],
            'required_clinical_staff_count' => ['required', 'integer', 'min:0', 'max:30'],
            'requires_chair' => ['required', 'boolean'],
            'required_chair_capabilities' => ['nullable', 'array'],
            'required_chair_capabilities.*' => ['string', 'max:80'],
            'booking_confirmation_mode' => ['nullable', Rule::enum(BookingConfirmationMode::class)],
            'is_bookable_online' => ['required', 'boolean'],
            'base_price' => ['nullable', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => filled($this->input('description')) ? $this->input('description') : null,
            'base_price' => filled($this->input('base_price')) ? $this->input('base_price') : null,
            'is_active' => $this->boolean('is_active'),
            'buffer_minutes' => $this->input('buffer_minutes') === null || $this->input('buffer_minutes') === '' ? 0 : $this->input('buffer_minutes'),
            'requires_specialist' => $this->boolean('requires_specialist'),
            'required_clinical_staff_count' => $this->input('required_clinical_staff_count') === null || $this->input('required_clinical_staff_count') === '' ? 0 : $this->input('required_clinical_staff_count'),
            'requires_chair' => $this->exists('requires_chair') ? $this->boolean('requires_chair') : true,
            'required_chair_capabilities' => CapabilityList::parse($this->input('required_chair_capabilities')),
            'booking_confirmation_mode' => filled($this->input('booking_confirmation_mode')) ? $this->input('booking_confirmation_mode') : null,
            'is_bookable_online' => $this->boolean('is_bookable_online'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'کد خدمت را وارد کنید.',
            'code.unique' => 'این کد خدمت قبلاً ثبت شده است.',
            'name.required' => 'نام خدمت را وارد کنید.',
            'default_duration_minutes.required' => 'مدت خدمت را به دقیقه وارد کنید.',
            'default_duration_minutes.min' => 'مدت خدمت باید حداقل ۵ دقیقه باشد.',
            'base_price.decimal' => 'مبلغ را با حداکثر دو رقم اعشار وارد کنید.',
        ];
    }
}
