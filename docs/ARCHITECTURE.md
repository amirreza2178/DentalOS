# معماری DentalOS

تاریخ: 2026-11-03  
وضعیت: Day 5 Public Booking و تأیید پذیرش پیاده شده است. حساب بیمار، صورتحساب و طرح درمان شروع نشده‌اند.

## وضعیت پیاده‌سازی

کد فعلی این مرزها را دارد:

- `users.role` و middleware `role` برای default-deny مسیرهای مدیریت.
- Policy برای Patient، User، Chair، TreatmentService، ClinicSetting، Appointment، WorkShift، Holiday، StaffSchedule، PatientVisit، WaitingQueueEntry و Gate روی `reception`.
- Actionهای Scheduling + Reception + `app/Actions/Booking/` (`BookOnlineAppointment`، `ApproveOnlineAppointment`، `RejectOnlineAppointment`).
- رزرو عمومی مهمان روی همان `AvailabilityService` / `ReserveAppointment` با `AppointmentSource::Online`.
- `JalaliCalendar` و `ClinicDay` برای مرز تاریخ/روز. ساعت‌ها با timezone برنامه (`UTC`) ذخیره می‌شوند.
- دیتابیس اجرایی فعلی MariaDB `dental_os` است. تست‌ها SQLite `:memory:` هستند.

کنترلرها نازک‌اند و اعتبارسنجی در Form Request است. Repository عمومی اضافه نشده است.

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
| اتصال اپ به MariaDB | تأیید شد (`dental_os` / `dental_os_app`) |
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
- `app/Actions/Booking/`
- `app/Actions/Billing/`
- `app/Actions/Reception/`
- `app/Enums/`
- `app/Models/`
- `app/Policies/`
- `app/Support/` (JalaliCalendar، PhoneNormalizer، ClinicDay)

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
| Reception | میز روزانه/هفتگی، Check-in، Visit، Queue، رویداد عملیاتی |
| Clinical | Plan/Task (بدون تصمیم‌گیری خودکار) |
| Billing | Invoice/Charge/Payment/Balance محاسبه‌ای |
| Audit | ActivityLog تغییرات مهم |
| Settings | ClinicSetting کلید/مقدار نسخه‌پذیر |

---

## 5. استراتژی همزمانی رزرو (Concurrency)

### مسئله
دو پذیرشگر + رزرو آنلاین ممکن است هم‌زمان یک صندلی/متخصص/بازه را بخواهند.

### رویکرد پیاده‌شده

1. یک transaction. رزرو، لغو و جابه‌جایی داخل همان تراکنش تمام می‌شوند. اگر جابه‌جایی نوبت جدید شکست بخورد، وضعیت نوبت قبلی برمی‌گردد.
2. منابع به ترتیب پایدار `(resource_type, resource_id)` قفل می‌شوند، به‌علاوه خود بیمار. جدول `reservation_locks` برای هر منبع upsert می‌شود و `lock_version` یکی زیاد می‌شود. این ردیف سپس `lockForUpdate` می‌شود و بعد ردیف دامنه (یونیت، کاربر، بیمار) هم قفل می‌شود. قفل‌کردن allocationهای موجود به‌تنهایی دو insert هم‌زمان در یک بازه خالی را نمی‌بندد؛ ردیف قفل همین جای خالی را می‌بندد.
3. گرامر SQLite لاراول `FOR UPDATE` را حذف می‌کند. افزایش `lock_version` روی SQLite یک write lock می‌سازد. روی MariaDB همان upsert به‌اضافه `SELECT … FOR UPDATE` است. داخل تراکنش SQLite مقدار `PRAGMA busy_timeout = 5000` تنظیم می‌شود.
4. پس از قفل، هم‌پوشانی نیمه‌باز `[start, end)` دوباره بررسی می‌شود. وضعیت‌های اشغال‌کننده: `pending_approval`، `confirmed`، `checked_in`، `in_progress`. وضعیت‌های `draft`، `cancelled`، `no_show`، `completed` و `rescheduled` ردیف allocation را نگه می‌دارند ولی بازه را نمی‌بندند. `ends_at` منبع تا پایان خدمت به‌علاوه `buffer_minutes` است. هم‌پوشانی خود بیمار روی پنجره خدمت است، بدون بافر.
5. اگر SQLite خطای `database is locked` / busy / deadlock بدهد، تا چهار بار با فاصله ۲۰۰ میلی‌ثانیه تکرار می‌شود. بعد از آن خطای دامنه «این بازه همین الان رزرو شد.» برمی‌گردد.
6. تست `CompetingReservationTest` دو فرایند PHP را روی یک فایل SQLite مشترک اجرا می‌کند؛ والد تراکنش را باز نگه می‌دارد و فرزند باید `conflict` بنویسد. در مجموعه کامل، فقط یک نوبت قطعی می‌ماند. SQLite قفل فاصله InnoDB را اثبات نمی‌کند.

### مرز تراکنش پذیرش (Day 4)

1. چک‌این، فراخوان بعدی، شروع خدمت، اتمام ویزیت و حذف از صف هر کدام داخل یک transaction با `lockForUpdate` روی نوبت/ویزیت/ورودی‌های صف همان روز اجرا می‌شوند.
2. یک نوبت حداکثر یک `patient_visits` دارد (`appointment_id` یکتا). چک‌این تکراری همان ویزیت/صف فعال را برمی‌گرداند و ردیف جدید نمی‌سازد.
3. ترتیب صف روزانه FIFO با `position` صحیح است. در هر `queue_date` حداکثر یک ورودی `called` فعال مجاز است.
4. این قفل‌ها روی SQLite تست شده‌اند؛ رفتار gap-lock اینودبی هنوز روی MariaDB اثبات نشده است.

### محاسبه ظرفیت
- ظرفیت یک بازه = منابع آزاد مطابق نیاز خدمت، داخل شیفت، خارج از break/holiday.
- خدمت می‌تواند specialist را الزامی یا اختیاری کند.
- تعداد clinical staff مورد نیاز از تعریف خدمت خوانده می‌شود؛ hardcode شش نفر ممنوع.
- صندلی‌ها در صورت تعریف capability باید match شوند.

جزئیات جدول‌ها: `DATABASE-DESIGN.md`.

---

## 6. تاریخ، زمان، جلالی

- ذخیره: ستون‌های `datetime` استاندارد. ساعت دیوار همان timezone برنامه است که هنوز `UTC` است. تا تأیید کلینیک تبدیل `Asia/Tehran` انجام نمی‌شود.
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
