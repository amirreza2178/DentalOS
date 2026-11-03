<?php

namespace App\Actions\Booking;

use App\Actions\Reception\RecordOperationalEvent;
use App\Enums\AppointmentStatus;
use App\Enums\OperationalEventType;
use App\Enums\UserRole;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Exceptions\Scheduling\OnlineBookingConflictException;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ApproveOnlineAppointment
{
    public function __construct(private RecordOperationalEvent $events) {}

    public function approve(Appointment $appointment, User $actor): Appointment
    {
        if (! $actor->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            throw new AuthorizationException('فقط پذیرش یا مدیر می‌تواند درخواست آنلاین را تأیید کند.');
        }

        return DB::transaction(function () use ($appointment, $actor): Appointment {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === AppointmentStatus::Confirmed && $locked->source->value === 'online') {
                return $locked;
            }

            if ($locked->status !== AppointmentStatus::PendingApproval) {
                throw new OnlineBookingConflictException('این درخواست دیگر در انتظار تأیید نیست.');
            }

            if (! $locked->status->canTransitionTo(AppointmentStatus::Confirmed)) {
                throw new InvalidAppointmentTransitionException($locked->status, AppointmentStatus::Confirmed);
            }

            $from = $locked->status->value;
            $locked->status = AppointmentStatus::Confirmed;
            $locked->approved_by_user_id = $actor->id;
            $locked->decided_at = now();
            $locked->decision_reason = null;
            $locked->save();

            $this->events->record(
                OperationalEventType::OnlineBookingApproved,
                $locked,
                $actor,
                $from,
                AppointmentStatus::Confirmed->value,
            );

            return $locked;
        }, 3);
    }
}
