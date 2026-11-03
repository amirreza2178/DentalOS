# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-03  
فاز: Day 6 — Clinical Workflow + Treatment Plan/Task + Follow-up + سخت‌سازی امنیتی

---

## فاز و مایلستون

Day 1 تا Day 5 در commit `2f16bc5e85a73344a1b71a93a31f428bba85859d` ثبت شده‌اند.  
Day 6 پیاده‌سازی شده و در commit جداگانه ثبت می‌شود.

اپ محلی به **MariaDB** وصل است: پایگاه `dental_os`، کاربر `dental_os_app@127.0.0.1`.

---

## Git

- شاخه: `master`
- Checkpoint Day 2–5: `2f16bc5e85a73344a1b71a93a31f428bba85859d`
- Remote: **وجود ندارد** — Push انجام نشد؛ URL مخزن DentalOS از کاربر خواسته می‌شود
- `.env` در `.gitignore` است و commit نمی‌شود

---

## MariaDB

| مورد | نتیجه |
|------|--------|
| اتصال Laravel | `pdo=ok` / `driver=mariadb` |
| migrate:status | ۱۶ migration Ran (batch 1–3) |
| جداول | ۲۷ جدول (`treatment_plans`, `treatment_tasks` اضافه شدند) |
| Migration Day 6 | `2026_11_03_160000_create_treatment_plans_and_tasks_tables` DONE |
| `migrate:fresh` | اجرا نشد |

PHPUnit همچنان از `sqlite :memory:` استفاده می‌کند (`phpunit.xml`)، نه از `dental_os`.

---

## آنچه در Day 6 پیاده شد

### Treatment Plan / Task
- جداول سبک `treatment_plans` و `treatment_tasks` (جدا از Appointment/Visit)
- ثبت/ویرایش برنامه فقط توسط Admin/Specialist
- تخصیص وظیفه بر اساس صلاحیت ثبت‌شده در `staff_profiles`
- رد تخصیص به نیروی فاقد صلاحیت
- نیروی درمانی فقط وظایف محول‌شده به خودش را می‌بیند
- وضعیت‌ها: pending → assigned → in_progress → completed / cancelled
- Audit از طریق `operational_events`

### Follow-up
- جدول موجود `follow_ups` گسترش یافت (`operational_note`, `outcome_note`, `patient_visit_id`)
- متخصص درخواست پیگیری ثبت می‌کند؛ نوبت خودکار ساخته نمی‌شود
- منشی فهرست actionable را می‌بیند، پیشنهاد/رد می‌کند
- تبدیل به نوبت فقط از مسیر موجود `appointments.create?follow_up_id=` با `FollowUpAuthorization`

### امنیت
- محدوده بیمار متخصص به نوبت‌های تخصیص‌یافته
- جستجوی میز پذیرش از متخصص برداشته شد (فقط admin/receptionist)
- Policy برای Visit با مالکیت متخصص
- منشی خلاصه بالینی را نمی‌بیند؛ فقط خلاصه عملیاتی
- Middleware `SecurityHeaders` + CSP سازگار با Blade/Vite
- Rate limit روی مسیرهای نوشتن بالینی

## آنچه عمداً ساخته نشد

EMR کامل، تشخیص/پیشنهاد خودکار درمان، OTP/SMS، پرداخت، حساب بیمار، seed آماری جعلی، سرویس خارجی Notification.

---

## دستورها و نتایج واقعی

| دستور | نتیجه |
|-------|--------|
| `php artisan migrate --force` | Day 6 migration DONE روی MariaDB |
| `php artisan test` | **64 passed / 388 assertions / ~21.5s** (SQLite :memory:) |
| `npm run build` | موفق؛ 61 ماژول |
| مرورگر | Login/CSP/صفحه عمومی Desktop+Mobile OK؛ صفحات authenticated با session HTTP 200 + CSP |

---

## کار بعدی (Day 7)

Billing پایه (Invoice/Payment/Balance) + گسترش ActivityLog + گزارش روزانه پذیرش؛ بدون درگاه آنلاین.
