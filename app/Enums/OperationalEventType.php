<?php

namespace App\Enums;

enum OperationalEventType: string
{
    case PatientCheckedIn = 'patient_checked_in';
    case VisitCreated = 'visit_created';
    case VisitStarted = 'visit_started';
    case VisitCompleted = 'visit_completed';
    case VisitCancelled = 'visit_cancelled';
    case QueueEntryCreated = 'queue_entry_created';
    case PatientCalled = 'patient_called';
    case QueueEntryRemoved = 'queue_entry_removed';
    case QueueServiceStarted = 'queue_service_started';
    case QueueCompleted = 'queue_completed';
    case OnlineBookingApproved = 'online_booking_approved';
    case OnlineBookingRejected = 'online_booking_rejected';
    case TreatmentPlanCreated = 'treatment_plan_created';
    case TreatmentPlanUpdated = 'treatment_plan_updated';
    case TreatmentPlanStatusChanged = 'treatment_plan_status_changed';
    case TreatmentTaskCreated = 'treatment_task_created';
    case TreatmentTaskAssigned = 'treatment_task_assigned';
    case TreatmentTaskStatusChanged = 'treatment_task_status_changed';
    case FollowUpCreated = 'follow_up_created';
    case FollowUpStatusChanged = 'follow_up_status_changed';

    public function label(): string
    {
        return match ($this) {
            self::PatientCheckedIn => 'چک‌این بیمار',
            self::VisitCreated => 'ایجاد ویزیت',
            self::VisitStarted => 'شروع ویزیت',
            self::VisitCompleted => 'اتمام ویزیت',
            self::VisitCancelled => 'لغو ویزیت',
            self::QueueEntryCreated => 'ورود به صف',
            self::PatientCalled => 'فراخوان بیمار',
            self::QueueEntryRemoved => 'حذف از صف',
            self::QueueServiceStarted => 'شروع خدمت در صف',
            self::QueueCompleted => 'اتمام صف',
            self::OnlineBookingApproved => 'تأیید رزرو آنلاین',
            self::OnlineBookingRejected => 'رد رزرو آنلاین',
            self::TreatmentPlanCreated => 'ثبت برنامه درمانی',
            self::TreatmentPlanUpdated => 'به‌روزرسانی برنامه درمانی',
            self::TreatmentPlanStatusChanged => 'تغییر وضعیت برنامه درمانی',
            self::TreatmentTaskCreated => 'ثبت وظیفه درمانی',
            self::TreatmentTaskAssigned => 'تخصیص وظیفه درمانی',
            self::TreatmentTaskStatusChanged => 'تغییر وضعیت وظیفه درمانی',
            self::FollowUpCreated => 'ثبت پیگیری',
            self::FollowUpStatusChanged => 'تغییر وضعیت پیگیری',
        };
    }
}
