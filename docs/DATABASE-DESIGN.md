# طراحی پایگاه‌داده DentalOS

تاریخ: 2026-11-03  
وضعیت: ۱۵ migration روی MariaDB `dental_os` Ran هستند. تست‌ها همچنان SQLite `:memory:` هستند.

## جدول‌های موجود

- `users` به‌علاوه `mobile`، `role`، `is_active`
- `staff_profiles`
- `patients` شامل `mobile_normalized`، `alt_phone`، `identity_status` و soft delete
- `chairs` به‌علاوه `capabilities` از نوع json
- `treatment_services` با مدت، `buffer_minutes`، نیاز متخصص، تعداد کادر بالینی، نیاز یونیت، قابلیت‌های لازم یونیت، `booking_confirmation_mode` و `is_bookable_online`
- `clinic_settings` کلید/مقدار
- `work_shifts`، `shift_breaks`، `staff_schedules`، `holidays`
- `follow_ups`، `appointments` (به‌علاوه `public_reference`، `decision_reason`، `decided_at`)، `appointment_resource_allocations`، `reservation_locks`
- `patient_visits`، `waiting_queue_entries`، `operational_events`

`follow_ups.converted_appointment_id` ایندکس است و کلید خارجی به `appointments` ندارد تا وابستگی دایره‌ای ساخته نشود.

`patients.mobile` یکتا نیست. `patients.national_id` اگر مقدار داشته باشد یکتا است. `users.mobile` برای حساب کاربری یکتا است و با موبایل مشترک خانواده‌ها یکی نیست.

هدف DB اپلیکیشن: **MariaDB**  
هدف تست خودکار: **SQLite in-memory** (سازگار با اسکلت Laravel فعلی)

---

## 1. اصول مدل داده

1. بیمار یک‌بار مدل می‌شود؛ از کپی پروفایل در نوبت خودداری شود (فقط FK + snapshot حداقلی در صورت نیاز قانونی/عملیاتی آینده).
2. مانده حساب فقط از مجموع charges/payments محاسبه می‌شود؛ فیلد cache اختیاری است و منبع حقیقت نیست.
3. Appointment، Visit، Queue، Plan/Task جدا هستند.
4. تخصیص منابع نوبت در جدول allocation صریح مدل می‌شود تا overlap قابل کنترل باشد.
5. Soft delete برای موجودیت‌های عملیاتی مهم؛ hard delete پیش‌فرض ممنوع برای Patient/Appointment مالی.
6. Enum وضعیت‌ها در PHP Enum + ستون string/یادداشت migration؛ مقادیر در BUSINESS-RULES.

---

## 2. ERD متنی (MVP)

```
users ─┬─< staff_profiles (role specialty flags)
       └─< patients (1:0..1 portal user)

patients ─┬─< appointments
          ├─< patient_visits
          ├─< treatment_plans ─< treatment_tasks
          ├─< invoices ─< invoice_items
          ├─< payments
          └─< follow_ups

treatment_services ─< appointments
treatment_services (required resource rules)

chairs (units)
staff_schedules / work_shifts / shift_breaks
holidays

appointments ─┬─< appointment_resource_allocations (chair|user)
              ├─ patient_visits (0..1)
              └─ waiting_queue_entries (via visit)

clinic_settings
activity_logs
```

---

## 3. فرهنگ داده — جداول پیشنهادی

### 3.1 `users`
کارکنان و در صورت نیاز حساب پرتال.

| ستون | نوع | توضیح |
|------|-----|-------|
| id | PK | |
| name | string | |
| email | string nullable unique | |
| mobile | string nullable unique | ترجیح احراز بیمار/کارکنان ایران |
| password | string | |
| is_active | bool | |
| locale | string | پیش‌فرض fa |
| remember_token | | |
| timestamps | | |

نقش‌ها: جدول `roles` + `role_user` ساده، یا ستون `role` enum در MVP.  
**تصمیم provisional:** جدول `roles`/`role_user` برای least-privilege تمیزتر.

### 3.2 `patients`
| ستون | نوع | توضیح |
|------|-----|-------|
| id | PK | |
| user_id | FK nullable unique | حساب پرتال |
| code | string unique | کد پرونده کلینیک |
| first_name, last_name | string | |
| national_id | string nullable indexed | |
| mobile | string indexed | |
| birth_date | date nullable | ذخیره Gregorian |
| gender | string nullable | |
| status | string | active/inactive |
| identity_status | string | verified / unverified_online |
| notes_admin | text nullable | غیر بالینی |
| timestamps, softDeletes | | |

### 3.3 `staff_profiles`
امتداد users برای کارکنان.

| ستون | نوع | توضیح |
|------|-----|-------|
| user_id | PK/FK | |
| staff_type | string | specialist / clinical_staff / receptionist / admin |
| license_status | string | licensed / student / other / unknown |
| title | string nullable | |
| can_take_clinical_notes | bool | پیش‌فرض بر اساس نقش |

> Doctor و ClinicalStaff جداول اجباری جدا نیستند؛ با `staff_profiles` + نقش‌ها نرمال می‌شوند تا از تکرار جلوگیری شود.

### 3.4 `chairs` (ClinicRoom/Chair/Unit)
| ستون | نوع | توضیح |
|------|-----|-------|
| id | PK | |
| code | string unique | U1, U2, ... |
| name | string | |
| capabilities | json nullable | مثلاً orthodontic_treatment |
| is_active | bool | |
| sort_order | int | |
| timestamps | | |

تعداد ردیف‌ها configurable است؛ هیچ hardcode شش‌تایی در کد.

### 3.5 `treatment_services`
| ستون | نوع | توضیح |
|------|-----|-------|
| id | PK | |
| code | string unique | |
| name_fa | string | |
| default_duration_minutes | int | الزامی؛ نه ۱ ساعت ثابت |
| buffer_minutes | int default 0 | |
| requires_specialist | bool | |
| required_clinical_staff_count | int default 0 | |
| requires_chair | bool default true | |
| required_chair_capabilities | json nullable | |
| booking_confirmation_mode | string nullable | null=ارث از کلینیک |
| is_bookable_online | bool | |
| base_price | decimal nullable | |
| is_active | bool | |

### 3.6 `work_shifts` و `staff_schedules` و `shift_breaks`
- `work_shifts`: الگوهای شیفت (مثلاً صبح شنبه تا چهارشنبه).
- `staff_schedules`: تخصیص کاربر به تاریخ/الگو + override.
- `shift_breaks`: بازه‌های استراحت داخل شیفت.

ستون‌های پیاده‌شده: `work_shifts` نام، `weekday` از ۰ (یکشنبه) تا ۶ (شنبه)، `starts_at`/`ends_at` از نوع time، `is_active`. `shift_breaks` به شیفت وصل است. `staff_schedules` یا به یک شیفت تکرارشونده وصل است، یا تاریخ و ساعت مشخص دارد، یا با `is_unavailable` همان روز را می‌بندد. ردیف تاریخ‌دار برای همان روز جایگزین الگوی تکرارشونده است و مرخصی بر هر دو مقدم است. availability فقط داخل شیفت فعال و خارج break/holiday و فقط برای کارکنان فعال با برنامه فعال محاسبه می‌شود.

### 3.7 `holidays`
| ستون | نوع |
|------|-----|
| id | PK |
| holiday_date | date unique |
| title | string |
| is_closed | bool |

### 3.8 `appointments`
| ستون | نوع | توضیح |
|------|-----|-------|
| id | PK | |
| patient_id | FK | |
| treatment_service_id | FK | |
| starts_at | datetime index | |
| ends_at | datetime index | |
| status | string index | |
| source | string | online / receptionist / system_suggested |
| confirmation_mode | string | snapshot سیاست هنگام ایجاد |
| booked_by_user_id | FK nullable | |
| approved_by_user_id | FK nullable | |
| rescheduled_from_id | FK nullable | |
| follow_up_id | FK nullable | |
| patient_notes | text nullable | غیر بالینی |
| public_reference | uuid nullable unique | کد پیگیری عمومی رزرو آنلاین |
| decision_reason | text nullable | دلیل رد پذیرش |
| decided_at | timestamp nullable | زمان تأیید/رد |
| timestamps, softDeletes | | |

ایندکس پیشنهادی: `(status, starts_at)`, `(patient_id, starts_at)`.

### 3.9 `appointment_resource_allocations`
قلب جلوگیری از double-booking.

| ستون | نوع | توضیح |
|------|-----|-------|
| id | PK | |
| appointment_id | FK | |
| resource_type | string | chair / specialist / clinical_staff |
| resource_id | unsignedBigInteger | chair_id یا user_id |
| starts_at | datetime | معمولاً برابر نوبت |
| ends_at | datetime | |
| is_required | bool | |
| timestamps | | |

ایندکس: `(resource_type, resource_id, starts_at, ends_at)`  
Integrity: برای appointmentهای با وضعیت فعال، overlap روی همین resource ممنوع (اعمال در سرویس + تست).

وضعیت‌های فعال اشغال‌کننده منابع (پیشنهادی):  
`pending_approval`, `confirmed`, `checked_in`, `in_progress`  
(قابل تنظیم؛ `draft` معمولاً اشغال نمی‌کند مگر hold موقت جداگانه)

### 3.10 `patient_visits`
| ستون | نوع | توضیح |
|------|-----|-------|
| id | PK | |
| patient_id | FK | |
| appointment_id | FK nullable unique | یک ویزیت فعال برای هر نوبت |
| status | string index | waiting/called/in_progress/completed/cancelled |
| checked_in_by_user_id | FK users nullable | |
| arrived_at | datetime nullable index | |
| called_at | datetime nullable | |
| started_at | datetime nullable | |
| completed_at | datetime nullable | |
| timestamps | | |

### 3.11 `waiting_queue_entries`
| ستون | نوع | توضیح |
|------|-----|-------|
| id | PK | |
| patient_visit_id | FK | |
| queue_date | date index | تاریخ مدنی در timezone برنامه |
| position | unsigned int | یکتا با queue_date |
| status | string index | waiting/called/in_service/completed/removed |
| arrived_at | datetime | |
| called_at | datetime nullable | |
| started_at | datetime nullable | |
| completed_at | datetime nullable | |
| called_by_user_id | FK users nullable | |
| timestamps | | |

قانون پیاده‌شده: حداکثر یک صف فعال (`waiting|called|in_service`) per visit در لایه دامنه. ترتیب روز با `position`. ایندکس `queue_day_status_position_idx`.

### 3.11b `operational_events`
رویدادهای حداقلی پذیرش: نوع رویداد، actor، subject morph، from/to status. بدون کپی داده بالینی حساس.

### 3.12 `treatment_plans` / `treatment_tasks`
Plan متعلق به patient (+ اختیاری created_by specialist).  
Task: plan_id، assignee_user_id nullable، status، due/scheduled window، بدون تجویز خودکار.

### 3.13 `follow_ups`
| ستون | نوع | توضیح |
|------|-----|-------|
| id | PK | |
| patient_id | FK | |
| treatment_service_id | FK nullable | |
| proposed_window_start | date | |
| proposed_window_end | date | |
| status | string | proposed…scheduled |
| created_by | FK nullable | |
| patient_confirmed_at | timestamp nullable | تأیید بیمار |
| authorized_by_user_id | FK users nullable | اقدام مجاز پذیرش |
| converted_appointment_id | unsignedBigInteger nullable index | بدون FK؛ فقط پس از اقدام مجاز |

### 3.14 `invoices` / `invoice_items` / `payments`
- Invoice به patient (+ اختیاری visit/appointment).
- Items: مبلغ، توضیح، service_id nullable.
- Payments: مبلغ، روش، paid_at، received_by.
- Balance = sum(items) − sum(payments) در لایه دامنه.

### 3.15 `clinic_settings`
کلید/مقدار: `booking_confirmation_mode`, `timezone`, `currency`, `no_show_grace_minutes`, ...

### 3.16 `activity_logs`
actor_user_id، action، subject_type/id، before/after json، ip، created_at.

---

## 4. موجودیت‌های کاندید که ادغام/حذف شدند

| کاندید | تصمیم |
|--------|--------|
| Doctor | → `users` + `staff_profiles.staff_type=specialist` |
| ClinicalStaff | → همان + clinical_staff |
| ClinicRoom جدا از Chair | در MVP Chair کافی است مگر کلینیک اتاق‌های چندصندلی تأیید کند |
| محاسبه balance جدول جدا | نه به‌عنوان منبع حقیقت |

---

## 5. استراتژی حذف

| موجودیت | استراتژی |
|---------|----------|
| Patient | soft delete + جلوگیری در صورت نوبت/مالی فعال |
| Appointment | soft delete؛ ترجیح وضعیت cancelled |
| Payment/Invoice | soft delete محدود؛ اصلاح با رکورد تعدیلی بهتر است |
| Chair/Service | غیرفعال‌سازی (`is_active`) به‌جای حذف |
| User | is_active=false |

---

## 6. یکپارچگی و ایندکس‌های مهم

1. جلوگیری overlap منابع در سرویس Scheduling داخل transaction.
2. Unique: `patients.code`, `chairs.code`, `users.email/mobile` (جایی که not null).
3. FKهای cascade محدود؛ ترجیح restrict روی مالی و نوبت.
4. چک `ends_at > starts_at` در validation و در صورت امکان DB check.
5. عدم ذخیره جلالی به‌عنوان ستون اصلی رویداد.
6. `reservation_locks` یکتا روی `(resource_type, resource_id)` با نام `reservation_lock_resource_unique` و ستون `lock_version`. این ردیف قفل نوشتن است، نه خود تخصیص.
7. ایندکس allocation با نام کوتاه `allocation_resource_window_idx` ساخته شده است. بافر فقط `ends_at` تخصیص را جلو می‌برد؛ `appointments.ends_at` پایان خود خدمت است.

## 7. ترتیب migration پیشنهادی (آینده، نه Day 1 کامل)

1. roles/users/staff_profiles  
2. patients  
3. chairs, treatment_services, clinic_settings, holidays  
4. shifts/schedules/breaks  
5. appointments + allocations  
6. visits + queue  
7. plans/tasks/follow_ups  
8. invoices/items/payments  
9. activity_logs  

Day 2 جدول‌های هویت، بیمار، کارکنان، یونیت، خدمت و تنظیمات را ساخت. Day 3 شیفت تا قفل رزرو را ساخت. Day 4 ویزیت، صف و رویداد عملیاتی را ساخت. طرح درمان، صورتحساب و audit کامل هنوز ساخته نشده‌اند.
