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

class RejectOnlineAppointment
{
    public function __construct(private RecordOperationalEvent $events) {}

    public function reject(Appointment $appointment, User $actor, ?string $reason = null): Appointment
    {
        if (! $actor->hasRole(UserRole::Admin, UserRole::Receptionist)) {
            throw new AuthorizationException('فقط پذیرش یا مدیر می‌تواند درخواست آنلاین را رد کند.');
        }

        return DB::transaction(function () use ($appointment, $actor, $reason): Appointment {
            $locked = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === AppointmentStatus::Cancelled && $locked->source->value === 'online') {
                return $locked;
            }

            if ($locked->status !== AppointmentStatus::PendingApproval) {
                throw new OnlineBookingConflictException('این درخواست دیگر در انتظار تأیید نیست.');
            }

            if (! $locked->status->canTransitionTo(AppointmentStatus::Cancelled)) {
                throw new InvalidAppointmentTransitionException($locked->status, AppointmentStatus::Cancelled);
            }

            $from = $locked->status->value;
            $locked->status = AppointmentStatus::Cancelled;
            $locked->approved_by_user_id = $actor->id;
            $locked->decided_at = now();
            $locked->decision_reason = filled($reason) ? $reason : null;
            $locked->save();

            $this->events->record(
                OperationalEventType::OnlineBookingRejected,
                $locked,
                $actor,
                $from,
                AppointmentStatus::Cancelled->value,
            );

            return $locked;
        }, 3);
    }
}
