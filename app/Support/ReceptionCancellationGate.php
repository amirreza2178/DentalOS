<?php

namespace App\Support;

use App\Enums\AppointmentStatus;
use App\Enums\QueueStatus;
use App\Enums\VisitStatus;
use App\Models\Appointment;
use App\Models\WaitingQueueEntry;

/**
 * Server and UI share one rule set for when reception may cancel.
 *
 * Explicit queue removal evidence = a queue row in status "removed" for the visit.
 * A cancelled Visit alone is not enough (could be ambiguous / non-queue).
 */
final class ReceptionCancellationGate
{
    /**
     * Persian message when cancellation must not be offered or accepted; null = allowed by gate.
     */
    public function blockReason(Appointment $appointment): ?string
    {
        if ($appointment->status === AppointmentStatus::Cancelled) {
            return null;
        }

        $visit = $appointment->relationLoaded('visit')
            ? $appointment->visit
            : $appointment->visit()->first();

        if ($visit !== null && $visit->status === VisitStatus::Completed) {
            return 'ویزیت این نوبت تمام شده است. لغو نوبت برای این وضعیت مجاز نیست؛ وضعیت را از جزئیات ویزیت بررسی کنید.';
        }

        if ($visit !== null && $visit->status->isActive()) {
            return 'بیمار این نوبت در صف یا در حال خدمت است. اول او را از صف خارج کنید، بعد در صورت نیاز نوبت را لغو کنید.';
        }

        $activeQueue = false;
        if ($visit !== null) {
            $activeQueue = WaitingQueueEntry::query()
                ->where('patient_visit_id', $visit->id)
                ->whereIn('status', QueueStatus::activeValues())
                ->exists();
        }

        if ($activeQueue) {
            return 'این بیمار هنوز ورودی فعال در صف دارد. اول او را از صف خارج کنید.';
        }

        if ($appointment->status !== AppointmentStatus::CheckedIn) {
            return null;
        }

        // Checked-in: cancel only after explicit queue removal evidence.
        if ($visit === null) {
            return 'نوبت پذیرش‌شده بدون ویزیت معتبر است. لغو خودکار مجاز نیست؛ وضعیت را با مدیر بررسی کنید.';
        }

        if ($visit->status !== VisitStatus::Cancelled) {
            return 'وضعیت ویزیت با پذیرش نوبت هم‌خوان نیست. لغو خودکار مجاز نیست؛ وضعیت را از جزئیات ویزیت بررسی کنید.';
        }

        $explicitlyRemoved = WaitingQueueEntry::query()
            ->where('patient_visit_id', $visit->id)
            ->where('status', QueueStatus::Removed->value)
            ->exists();

        if (! $explicitlyRemoved) {
            return 'ویزیت لغو شده اما خروج صریح از صف ثبت نشده است. لغو نوبت خودکار مجاز نیست؛ وضعیت را با مدیر بررسی کنید.';
        }

        return null;
    }
}
