# برنامه پیاده‌سازی DentalOS (پنجره حدوداً ۸ روزه)

تاریخ به‌روزرسانی: 2026-11-03

---

## نقشه روزها (پیشنهادی)

### Day 1 — Foundation و مشخصات ✅
- ممیزی محیط، مستندات، Laravel 13 در ریشه، Git، npm، تست SQLite

### Day 2 — هویت، بیمار، کارکنان، یونیت، خدمت، تنظیمات ✅
- نقش، Policy، بیمار، پروفایل کارکنان، کاتالوگ، RTL

### Day 3 — موتور Scheduling ✅
- شیفت/تعطیلی/availability/appointment/allocation/قفل رزرو

### Day 4 — میز پذیرش ✅
- تقویم روزانه/هفتگی
- جستجوی بیمار روی میز
- Check-in، PatientVisit، WaitingQueue، operational_events
- رزرو داخلی از قبل در Day 3 موجود بود و دوباره ساخته نشد
- MariaDB بررسی شد؛ اتصال اپ هنوز برقرار نشد

### Day 5 — Public Booking + تأیید پذیرش ✅
- رزرو آنلاین مهمان بدون حساب/OTP
- AvailabilityService مشترک + pending_approval
- تأیید/رد پذیرش با قفل و audit رویداد
- محدودیت مشاهده متخصص به نوبت خودش
- MariaDB migrate غیرتخریبی ستون‌های Day 5

### Day 6 — بالینی سبک + Follow-up ✅
- TreatmentPlan/Task با صلاحیت و Policy
- Follow-up پیشنهادی با تبدیل امن به نوبت
- محدودسازی دسترسی متخصص و Security Headers/CSP

### Day 7 — هویت کلینیک + مرکز فرمان پذیرش + امنیت ✅
- ClinicIdentity مرکزی و برندینگ عمومی
- Reception command center عملیاتی
- سخت‌سازی confirmation/رمز محلی/audit لغو و جابه‌جایی
- Billing عمداً به Day 8 موکول شد (اولویت Day 7 محصولی)

### Day 8 — Billing پایه + کیفیت
- Invoice/Payment/Balance بدون درگاه
- گزارش مالی/عملیاتی روزانه پذیرش
- در صورت آمادگی: `dental_os_test` برای concurrency InnoDB
- حساب بیمار + OTP پس از سرویس پیامک (در صورت تأیید)

---

## کار بعدی دقیق (Day 8)

1. صورتحساب و پرداخت حضوری بدون درگاه.
2. گسترش audit برای billing.
3. حساب بیمار و SMS را شروع نکنید مگر تصمیم صریح کلینیک.

---

## چیزهایی که عمداً به تعویق افتاده‌اند

- migration طرح درمان، صورتحساب و audit کامل
- seed داده جعلی آماری
- درگاه پرداخت
- SPA
- multi-tenancy
- walk-in بدون نوبت
- اتوماسیون no-show
