<?php

namespace App\Http\Requests;

use App\Models\Appointment;

class RescheduleAppointmentRequest extends StoreAppointmentRequest
{
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');

        return $appointment instanceof Appointment
            && ($this->user()?->can('reschedule', $appointment) ?? false);
    }
}
