# معماری DentalOS

تاریخ: 2026-11-02  
وضعیت: provisional برای MVP تک‌کلینیک، با مسیر رشد کنترل‌شده.

---

## 1. اصول معماری

1. ساده‌ترین معماری قابل نگهداری Laravel.
2. منطق کسب‌وکار خارج از Controller.
3. یک دیتابیس رابطه‌ای (هدف: MariaDB).
4. بدون microservice، بدون repository اجباری، بدون abstraction زودهنگام.
5. موجودیت‌های دامنه از هم تفکیک شوند: Appointment ≠ Visit ≠ Queue ≠ Plan/Task.
6. یک موتور availability مشترک برای رزرو آنلاین و پذیرش.
7. `clinic_id` اختیاری/آماده‌سازی آینده فقط در صورت نیاز واقعی؛ در MVP تک‌کلینیک از پیچیدگی tenant اجتناب می‌شود ولی شناسه‌های منابع به‌صورت configurable نگه داشته می‌شوند.

---

## 2. پشته فنی تأییدشده محیط Day 1

| جزء | نسخه/وضعیت |
|-----|-------------|
| PHP | 8.4.24 |
| Composer | 2.10.2 |
| Laravel Framework | 13.39.0 |
| Node | v26.7.0 |
| npm | 11.19.0 |
| Vite + Tailwind | از اسکلت Laravel 13 |
| PHPUnit | ^12.5 (اسکلت پروژه) |
| MariaDB Server | 11.4.12 — سرویس Running، پورت 3306 باز |
| اتصال اپ به MariaDB | **هنوز تأیید نشد** (خطای auth_gssapi با root خالی) |
| تست DB | SQLite `:memory:` در `phpunit.xml` |

UI: Blade + Tailwind؛ Alpine فقط در تعاملات ضروری تقویم/صف.

---

## 3. لایه‌بندی پیشنهادی اپلیکیشن

```
HTTP (routes/web.php, routes/auth)
  → FormRequest (اعتبارسنجی ورودی)
  → Policy/Gate (مجوز)
  → Controller نازک
  → Action/Service متمرکز دامنه
  → Eloquent Models + DB Transactions
  → Blade Views (fa-RTL)
```

### پوشه‌بندی پیشنهادی (تدریجی، نه همه در Day 1)

- `app/Actions/Scheduling/`
- `app/Actions/Billing/`
- `app/Actions/Reception/`
- `app/Enums/`
- `app/Models/`
- `app/Policies/`
- `app/Support/Jalali/` (تبدیل نمایش؛ ذخیره UTC/DB استاندارد)

از ایجاد Interface/Repository برای هر مدل خودداری شود مگر نقطه جایگزینی واقعی وجود داشته باشد.

---

## 4. ماژول‌های منطقی MVP

| ماژول | مسئولیت |
|-------|---------|
| Identity & Access | کاربران، نقش‌ها، سیاست دسترسی |
| Patients | پرونده بیمار، جستجو، پرتال self |
| Catalog | TreatmentService، مدت، منابع لازم، سیاست تأیید |
| Resources | Chair/Unit، قابلیت‌ها، شیفت، تعطیلات |
| Scheduling | Availability، Reservation، قفل همزمانی |
| Reception | Check-in، Queue، وضعیت روزانه |
| Clinical | Plan/Task (بدون تصمیم‌گیری خودکار) |
| Billing | Invoice/Charge/Payment/Balance محاسبه‌ای |
| Audit | ActivityLog تغییرات مهم |
| Settings | ClinicSetting کلید/مقدار نسخه‌پذیر |

---

## 5. استراتژی همزمانی رزرو (Concurrency)

### مسئله
دو پذیرشگر + رزرو آنلاین ممکن است هم‌زمان یک صندلی/متخصص/بازه را بخواهند.

### رویکرد MVP (تأیید provisional)

1. **Transaction** با سطح ایزوله مناسب (پیش‌فرض InnoDB `REPEATABLE READ` در MariaDB).
2. برای هر منبع required در بازه زمانی، ردیف‌های تداخلی `appointment_resource_allocations` با شرایط وضعیت فعال قفل/بررسی می‌شوند.
3. **Unique/Exclusion منطقی:** جلوگیری از overlap با قیود کاربردی + ایندکس‌های پشتیبان؛ در MariaDB فاقد EXCLUDE constraint کامل PostgreSQL هستیم، بنابراین:
   - بررسی overlap داخل تراکنش
   - قفل روی منابع درگیر (`lockForUpdate` روی chair/staff rows یا allocation ranges)
   - وضعیت‌های آزادکننده منابع (`cancelled`, `no_show`, `rescheduled`) در شرط overlap وارد نشوند
4. در صورت تعارض: rollback + خطای دامنه قابل نمایش به کاربر («این بازه همین الان رزرو شد»).
5. تست Feature همزمانی با دو درخواست متوالی/موازی شبیه‌سازی‌شده در PHPUnit.

### محاسبه ظرفیت
- ظرفیت یک بازه = منابع آزاد مطابق نیاز خدمت، داخل شیفت، خارج از break/holiday.
- خدمت می‌تواند specialist را الزامی یا اختیاری کند.
- تعداد clinical staff مورد نیاز از تعریف خدمت خوانده می‌شود؛ hardcode شش نفر ممنوع.
- صندلی‌ها در صورت تعریف capability باید match شوند.

جزئیات جدول‌ها: `DATABASE-DESIGN.md`.

---

## 6. تاریخ، زمان، جلالی

- ذخیره: ستون‌های `datetime`/`timestamp` استاندارد DB (ترجیحاً UTC یا timezone ثابت اپ — تصمیم در `DECISIONS.md`).
- نمایش: تبدیل به جلالی در لایه presentation.
- ورودی کاربر جلالی → تبدیل به Gregorian/instant قبل از persistence.
- هرگز تاریخ جلالی را به‌عنوان تنها منبع حقیقت در DB ذخیره نکنید.

---

## 7. امنیت

- Authentication: session-based Laravel برای staff و patient portal.
- Authorization: Policies + نقش‌ها (spatie یا enum نقش ساده MVP — تصمیم provisional در DECISIONS).
- Mass assignment محافظت‌شده؛ FormRequest اجباری برای writeها.
- جداسازی queryهای پرتال با `auth()->id()` / `patient_id` مالک.
- Audit برای تغییرات نوبت، پرداخت، نقش، تنظیمات، دسترسی notes.

---

## 8. فرانت‌اند

- پذیرش: desktop-first، RTL، جداول فشرده، میانبرهای فرم.
- پرتال بیمار: mobile-first پاسخ‌گو.
- تقویم هفتگی/ماهانه پذیرش با بار حداقلی JS.
- بدون تبدیل زودهنگام به SPA.

---

## 9. مسیر رشد چندکلینیکی (بدون پیچیدگی الان)

آماده‌سازی سبک:
- اجتناب از فرض‌های hardcode یک صندلی/یک شیفت.
- نام‌گذاری تنظیمات به‌صورت scoped.
- اضافه کردن `clinic_id` فقط وقتی نیاز واقعی و migration برنامه‌ریزی‌شده وجود دارد (نه الان به‌زور روی همه جداول اگر ارزش ندارد).

تصمیم Day 1: **تک‌کلینیک منطقی**؛ جداول بدون tenant اجباری، با طراحی قابل گسترش.

---

## 10. ریسک‌های معماری

| ریسک | شدت | کاهش |
|------|-----|------|
| نبود EXCLUDE constraint در MariaDB | بالا | تراکنش + قفل + تست همزمانی |
| دسترسی بیش‌ازحد پذیرش به داده بالینی | بالا | Policy سخت + تست مجوز |
| تبدیل تاریخ جلالی نادرست | متوسط | لایه تبدیل واحد + تست |
| اتصال MariaDB محلی هنوز verify نشده | بالا برای استقرار | تنظیم کاربر DB صریح؛ sqlite فقط برای foundation/test |
| over-modeling قبل از تأیید کلینیک | متوسط | migration حداقلی مرحله‌ای |
