# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-03  
فاز: Day 5 — Public Patient Booking + تأیید/رد پذیرش + سخت‌سازی امنیتی

---

## فاز و مایلستون

Day 1 commit شده است.  
Day 2 تا Day 5 پیاده‌سازی شده‌اند و **commit نشده‌اند**.

اپ محلی به **MariaDB** وصل است: پایگاه `dental_os`، کاربر `dental_os_app@127.0.0.1`. SQLite و بکاپش حفظ شده‌اند.

---

## Git

- شاخه: `master`
- Commit اولیه: `33541fbed1d1f25e09f80083e16ce9d1667cfda4`
- Day 2–5 uncommitted
- Push انجام نشده
- `.env` در `.gitignore` است و stage نمی‌شود

---

## MariaDB

| مورد | نتیجه |
|------|--------|
| اتصال Laravel | `pdo=ok` روی MariaDB 11.4.12 |
| migrate:status | ۱۵ migration Ran (batch 1–2) |
| جداول | ۲۵ جدول (بدون جدول جدید؛ فقط ستون‌های Day 5) |
| کاربران | ۱ admin روی MariaDB |
| خدمات/نوبت | ۰ تا زمان seed واقعی |

رمزها در مستندات نیست. `migrate:fresh` / wipe اجرا نشد.

PHPUnit از `sqlite :memory:` استفاده می‌کند (`phpunit.xml`)، نه از `dental_os`.

---

## آنچه در Day 5 پیاده شد

- صفحه عمومی `/` و جریان رزرو مرحله‌ای `/book…`
- فقط خدمات `is_active` + `is_bookable_online`
- اسلات از همان `AvailabilityService`؛ رد زمان گذشته
- ثبت درخواست با `source=online` و معمولاً `pending_approval`
- پرونده تماس جدید با `identity_status=unverified_online`؛ بدون اتصال خودکار به بیمار موجود با موبایل
- کد پیگیری `public_reference` (UUID)
- پنل پذیرش: `/booking-requests` تأیید/رد با قفل ردیفی، idempotent approve، آزادسازی منابع در رد
- محدودیت متخصص به نوبت‌های تخصیص‌یافته خودش
- Rate limit عمومی، پاکسازی HTML، جلوگیری از double-submit

## آنچه عمداً ساخته نشد

حساب/داشبورد بیمار، OTP/SMS، پرداخت، پرتال پرونده پزشکی، ادغام خودکار پرونده‌ها، walk-in، no-show خودکار.

---

## دستورها و نتایج واقعی

| دستور | نتیجه |
|-------|--------|
| `php artisan migrate --force` | `2026_11_03_140000_add_online_booking_columns` DONE روی MariaDB |
| `php artisan test` | **57 passed / 340 assertions / ~18.8s** |
| `npm run build` | موفق؛ 61 ماژول؛ Vazirmatn در خروجی |

---

## کار بعدی (Day 6)

طرح درمان سبک + Follow-up امن؛ یا در صورت نیاز کلینیک: حساب بیمار با OTP واقعی پس از تأیید سرویس پیامک.
