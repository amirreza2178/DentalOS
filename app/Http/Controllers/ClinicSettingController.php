<?php

namespace App\Http\Controllers;

use App\Enums\BookingConfirmationMode;
use App\Http\Requests\ClinicSettingRequest;
use App\Models\ClinicSetting;
use App\Support\ClinicIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClinicSettingController extends Controller
{
    public function edit(ClinicIdentity $identity): View
    {
        $this->authorize('viewAny', ClinicSetting::class);

        $values = [];
        foreach (ClinicSetting::keys() as $key) {
            $values[$key] = ClinicSetting::getValue($key);
        }

        return view('settings.edit', [
            'values' => $values,
            'modes' => BookingConfirmationMode::cases(),
            'applicationTimezone' => config('app.timezone'),
            'identityDefaults' => [
                'clinic_name' => ClinicIdentity::DEFAULT_CLINIC_DISPLAY_NAME,
                'doctor_display_name' => ClinicIdentity::DEFAULT_DOCTOR_DISPLAY_NAME,
                'doctor_title' => ClinicIdentity::DEFAULT_DOCTOR_TITLE,
            ],
            'resolved' => $identity->toViewData(),
        ]);
    }

    public function update(ClinicSettingRequest $request): RedirectResponse
    {
        foreach (ClinicSetting::keys() as $key) {
            ClinicSetting::putValue($key, $request->validated($key));
        }

        return redirect()->route('settings.edit')->with('status', 'تنظیمات ذخیره شد.');
    }
}
