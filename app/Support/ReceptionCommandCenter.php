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
     * @param  'day'|'all'  $onlineScope
     * @param  'focus'|'all'  $followUpScope
     * @return array<string, mixed>
     */
    public function build(
        User $user,
        string $gregorian,
        ClinicDay $clinicDay,
        string $onlineScope = 'day',
        string $followUpScope = 'focus',
    ): array {
        $onlineScope = $onlineScope === 'all' ? 'all' : 'day';
        $followUpScope = $followUpScope === 'all' ? 'all' : 'focus';
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
        $pendingOnlineAllCount = 0;
        $pendingOnlineScopedCount = 0;
        $actionableFollowUps = collect();
        $followUpAllCount = 0;
        $followUpScopedCount = 0;
        $alerts = collect();

        if ($canOperate) {
            $pendingOnlineAllCount = Appointment::query()
                ->where('source', AppointmentSource::Online->value)
                ->where('status', AppointmentStatus::PendingApproval->value)
                ->count();

            $pendingOnlineScopedQuery = Appointment::query()
                ->where('source', AppointmentSource::Online->value)
                ->where('status', AppointmentStatus::PendingApproval->value)
                ->when(
                    $onlineScope === 'day',
                    fn ($query) => $query
                        ->where('starts_at', '>=', $dayStart)
                        ->where('starts_at', '<', $dayEnd),
                );

            $pendingOnlineScopedCount = (clone $pendingOnlineScopedQuery)->count();

            $pendingOnline = (clone $pendingOnlineScopedQuery)
                ->with(['patient:id,first_name,last_name,code,mobile', 'treatmentService:id,name'])
                ->orderBy('starts_at')
                ->limit(8)
                ->get();

            $followUpAllCount = FollowUp::query()
                ->whereIn('status', [
                    FollowUpStatus::Proposed->value,
                    FollowUpStatus::OfferedToPatient->value,
                    FollowUpStatus::Accepted->value,
                ])
                ->count();

            /*
             | Focus scope keeps overdue + windows overlapping the selected ClinicDay.
             | Independent follow-ups for the same patient/service stay separate.
             */
            $followUpScopedQuery = FollowUp::query()
                ->whereIn('status', [
                    FollowUpStatus::Proposed->value,
                    FollowUpStatus::OfferedToPatient->value,
                    FollowUpStatus::Accepted->value,
                ])
                ->when(
                    $followUpScope === 'focus',
                    fn ($query) => $query->where(function ($scoped) use ($gregorian): void {
                        $scoped
                            ->where(function ($overlap) use ($gregorian): void {
                                $overlap
                                    ->whereDate('proposed_window_start', '<=', $gregorian)
                                    ->whereDate('proposed_window_end', '>=', $gregorian);
                            })
                            ->orWhereDate('proposed_window_end', '<', $gregorian);
                    }),
                );

            $followUpScopedCount = (clone $followUpScopedQuery)->count();

            $actionableFollowUps = (clone $followUpScopedQuery)
                ->with(['patient:id,first_name,last_name,code', 'treatmentService:id,name'])
                ->orderBy('proposed_window_end')
                ->orderBy('proposed_window_start')
                ->orderBy('id')
                ->limit(8)
                ->get()
                ->values();

            if ($pendingOnlineScopedCount > 0) {
                $scopeHint = $onlineScope === 'day' ? ' برای این روز' : '';
                $alerts->push([
                    'tone' => 'attention',
                    'label' => $pendingOnlineScopedCount.' درخواست آنلاین در انتظار تأیید'.$scopeHint,
                    'href' => '#online-heading',
                ]);
            } elseif ($onlineScope === 'day' && $pendingOnlineAllCount > 0) {
                $alerts->push([
                    'tone' => 'neutral',
                    'label' => $pendingOnlineAllCount.' درخواست آنلاین در روزهای دیگر در انتظار تأیید',
                    'href' => '#online-heading',
                ]);
            }

            if ($followUpScopedCount > 0) {
                $alerts->push([
                    'tone' => 'neutral',
                    'label' => $followUpScopedCount.' پیگیری نیازمند اقدام پذیرش',
                    'href' => '#followups-heading',
                ]);
            } elseif ($followUpScope === 'focus' && $followUpAllCount > 0) {
                $alerts->push([
                    'tone' => 'neutral',
                    'label' => $followUpAllCount.' پیگیری قابل اقدام خارج از تمرکز امروز',
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

        $nextClinical = $schedule
            ->filter(fn (Appointment $appointment) => in_array($appointment->status, [
                AppointmentStatus::InProgress,
                AppointmentStatus::CheckedIn,
                AppointmentStatus::Confirmed,
                AppointmentStatus::PendingApproval,
            ], true))
            ->sortBy(function (Appointment $appointment) use ($now): string {
                $priority = match ($appointment->status) {
                    AppointmentStatus::InProgress => '0',
                    AppointmentStatus::CheckedIn => '1',
                    AppointmentStatus::Confirmed => $appointment->starts_at->lt($now) ? '2' : '3',
                    default => '4',
                };

                return $priority.'|'.$appointment->starts_at->format('Y-m-d H:i:s').'|'.$appointment->id;
            })
            ->first();

        return [
            'specialistScoped' => $specialistOnly,
            'canOperate' => $canOperate,
            'onlineScope' => $onlineScope,
            'pendingOnlineAllCount' => $pendingOnlineAllCount,
            'pendingOnlineScopedCount' => $pendingOnlineScopedCount,
            'followUpScope' => $followUpScope,
            'followUpAllCount' => $followUpAllCount,
            'followUpScopedCount' => $followUpScopedCount,
            'schedule' => $schedule,
            'cancelledOrRescheduled' => $cancelledOrRescheduled,
            'upcoming' => $upcoming,
            'nextClinical' => $nextClinical,
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
                'pending_online' => $pendingOnlineScopedCount,
                'follow_ups' => $followUpScopedCount,
                'cancelled_rescheduled' => $cancelledOrRescheduled->count(),
            ],
            'refreshedAt' => Carbon::now($clinicDay->timezone()),
        ];
    }
}
