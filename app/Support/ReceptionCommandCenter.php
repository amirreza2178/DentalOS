<?php

namespace App\Support;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\FollowUpStatus;
use App\Enums\QueueStatus;
use App\Enums\ResourceType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\FollowUp;
use App\Models\User;
use App\Models\WaitingQueueEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class ReceptionCommandCenter
{
    /**
     * @return array<string, mixed>
     */
    public function build(User $user, string $gregorian, ClinicDay $clinicDay): array
    {
        $dayStart = $clinicDay->startOfDay($gregorian);
        $dayEnd = $clinicDay->endExclusive($gregorian);
        $specialistOnly = $user->hasRole(UserRole::Specialist)
            && ! $user->hasRole(UserRole::Admin, UserRole::Receptionist);
        $canOperate = $user->can('operate', 'reception');

        $appointments = Appointment::query()
            ->with([
                'patient:id,first_name,last_name,code,mobile',
                'treatmentService:id,name,default_duration_minutes',
                'visit.latestQueueEntry',
                'allocations',
            ])
            ->where('starts_at', '>=', $dayStart)
            ->where('starts_at', '<', $dayEnd)
            ->whereNotIn('status', [AppointmentStatus::Draft->value])
            ->when(
                $specialistOnly,
                fn ($query) => $query->whereHas(
                    'allocations',
                    fn ($allocation) => $allocation
                        ->where('resource_type', ResourceType::Specialist->value)
                        ->where('resource_id', $user->id),
                ),
            )
            ->orderBy('starts_at')
            ->get();

        $activeStatuses = [
            AppointmentStatus::Confirmed->value,
            AppointmentStatus::CheckedIn->value,
            AppointmentStatus::InProgress->value,
            AppointmentStatus::PendingApproval->value,
        ];

        $schedule = $appointments->filter(
            fn (Appointment $appointment) => in_array($appointment->status->value, $activeStatuses, true)
                || $appointment->visit !== null,
        )->values();

        $cancelledOrRescheduled = $appointments->filter(
            fn (Appointment $appointment) => in_array($appointment->status, [
                AppointmentStatus::Cancelled,
                AppointmentStatus::Rescheduled,
            ], true),
        )->values();

        $now = now($clinicDay->timezone());
        $upcoming = $schedule->filter(
            fn (Appointment $appointment) => $appointment->starts_at->gte($now)
                && in_array($appointment->status, [
                    AppointmentStatus::Confirmed,
                    AppointmentStatus::PendingApproval,
                ], true)
                && $appointment->visit === null,
        )->take(4)->values();

        $queue = $specialistOnly
            ? collect()
            : WaitingQueueEntry::query()
                ->with([
                    'visit.patient:id,first_name,last_name,code',
                    'visit.appointment.treatmentService:id,name',
                ])
                ->forClinicDate($gregorian)
                ->active()
                ->orderBy('position')
                ->get();

        $waiting = $queue->where('status', QueueStatus::Waiting)->values();
        $called = $queue->where('status', QueueStatus::Called)->values();
        $inService = $queue->where('status', QueueStatus::InService)->values();

        $pendingOnline = collect();
        $actionableFollowUps = collect();
        $alerts = collect();

        if ($canOperate) {
            $pendingOnline = Appointment::query()
                ->with(['patient:id,first_name,last_name,code,mobile', 'treatmentService:id,name'])
                ->where('source', AppointmentSource::Online->value)
                ->where('status', AppointmentStatus::PendingApproval->value)
                ->orderBy('starts_at')
                ->limit(8)
                ->get();

            $actionableFollowUps = FollowUp::query()
                ->with(['patient:id,first_name,last_name,code', 'treatmentService:id,name'])
                ->whereIn('status', [
                    FollowUpStatus::Proposed->value,
                    FollowUpStatus::OfferedToPatient->value,
                    FollowUpStatus::Accepted->value,
                ])
                ->orderBy('proposed_window_start')
                ->orderByDesc('id')
                ->limit(8)
                ->get();

            if ($pendingOnline->isNotEmpty()) {
                $alerts->push([
                    'tone' => 'attention',
                    'label' => $pendingOnline->count().' درخواست آنلاین در انتظار تأیید',
                    'href' => '#online-heading',
                ]);
            }

            if ($actionableFollowUps->isNotEmpty()) {
                $alerts->push([
                    'tone' => 'neutral',
                    'label' => $actionableFollowUps->count().' پیگیری نیازمند اقدام پذیرش',
                    'href' => '#followups-heading',
                ]);
            }

            $lateCount = $schedule->filter(
                fn (Appointment $appointment) => $appointment->status === AppointmentStatus::Confirmed
                    && $appointment->visit === null
                    && $appointment->starts_at->lt($now),
            )->count();

            if ($lateCount > 0) {
                $alerts->push([
                    'tone' => 'warning',
                    'label' => $lateCount.' نوبت قطعی از ساعت مقرر گذشته و هنوز چک‌این نشده',
                    'href' => '#day-appointments-heading',
                ]);
            }
        }

        return [
            'specialistScoped' => $specialistOnly,
            'canOperate' => $canOperate,
            'schedule' => $schedule,
            'cancelledOrRescheduled' => $cancelledOrRescheduled,
            'upcoming' => $upcoming,
            'queue' => $queue,
            'waiting' => $waiting,
            'called' => $called,
            'inService' => $inService,
            'pendingOnline' => $pendingOnline,
            'actionableFollowUps' => $actionableFollowUps,
            'alerts' => $alerts,
            'counts' => [
                'schedule' => $schedule->count(),
                'waiting' => $waiting->count(),
                'called' => $called->count(),
                'in_service' => $inService->count(),
                'pending_online' => $pendingOnline->count(),
                'follow_ups' => $actionableFollowUps->count(),
                'cancelled_rescheduled' => $cancelledOrRescheduled->count(),
            ],
            'refreshedAt' => Carbon::now($clinicDay->timezone()),
        ];
    }
}
