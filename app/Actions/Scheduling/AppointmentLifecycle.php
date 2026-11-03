<?php

namespace App\Actions\Scheduling;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class AppointmentLifecycle
{
    public function transition(Appointment $appointment, AppointmentStatus $to, User $actor): Appointment
    {
        return DB::transaction(function () use ($appointment, $to, $actor): Appointment {
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if (! $appointment->status->canTransitionTo($to)) {
                throw new InvalidAppointmentTransitionException($appointment->status, $to);
            }

            if ($to === AppointmentStatus::Confirmed && ! $actor->hasRole(UserRole::Admin, UserRole::Receptionist)) {
                throw new AuthorizationException('فقط پذیرش یا مدیر می‌تواند نوبت را قطعی کند.');
            }

            if ($to === AppointmentStatus::Completed && ! $actor->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist)) {
                throw new AuthorizationException('تکمیل نوبت فقط با تأیید پذیرش، متخصص یا مدیر مجاز است.');
            }

            if (in_array($to, [AppointmentStatus::CheckedIn, AppointmentStatus::InProgress, AppointmentStatus::NoShow], true)
                && ! $actor->hasRole(UserRole::Admin, UserRole::Receptionist)) {
                throw new AuthorizationException('این تغییر وضعیت برای نقش شما مجاز نیست.');
            }

            $appointment->status = $to;

            if ($to === AppointmentStatus::Confirmed) {
                $appointment->approved_by_user_id = $actor->id;
            }

            $appointment->save();

            return $appointment;
        });
    }
}
