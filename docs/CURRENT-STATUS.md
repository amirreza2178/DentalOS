# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-03  
فاز: Day 8 — Operational Excellence و تجربه کاربری end-to-end  
پیش از آن: Day 7 — هویت کلینیک + مرکز فرمان پذیرش + سخت‌سازی امنیتی

---

## فاز و مایلستون

Day 1 تا Day 5: `2f16bc5e85a73344a1b71a93a31f428bba85859d`  
Day 6: `8624aa99b204e7d6f67fd320026aafcc39b411c0`  
Day 7: `67fa2d8b10683fbe0badd51a96fbeef28bfe2560`  
Day 8: پس از commit این گزارش ثبت می‌شود

اپ محلی به **MariaDB** وصل است: پایگاه `dental_os`، کاربر `dental_os_app@127.0.0.1`.

---

## Git

- شاخه: `master`
- Remote: `origin` = `https://github.com/amirreza2178/DentalOS.git`
- `.env` و `storage/app/private/*` در `.gitignore` هستند

---

## MariaDB

| مورد | نتیجه |
|------|--------|
| اتصال Laravel | `pdo=ok` / `driver=mariadb` |
| migrate:status | بدون migration جدید Day 8 |
| `dental_os_test` | **وجود ندارد** — تست همزمانی InnoDB همچنان مسدود |
| `migrate:fresh` | اجرا نشد |

PHPUnit از `sqlite :memory:` استفاده می‌کند.

---

## آنچه در Day 8 پیاده شد

### بیمار / رزرو عمومی
- میان‌بر تاریخ جلالی (امروز/فردا/پس‌فردا) روی انتخاب تاریخ و زمان
- فرم با نشان الزام، خطای فیلدی، و تأکید صریح «درخواست ≠ نوبت قطعی»
- confirmation با کپی کد پیگیری و چاپ؛ بدون افشای نام/موبایل

### پذیرش
- ورود مستقیم به `reception.day` پس از login
- چک‌این روی مرکز فرمان می‌ماند (نه پرش اجباری به ویزیت)
- تأیید آنلاین ردیفی برای `pending_approval` + badge وضعیت
- رد آنلاین در sidebar با فیلد دلیل و بازگشت به مرکز فرمان
- هشدارهای عملیاتی با anchor به بخش مربوط
- تازه‌سازی آرام JS هنگام بیکاری به‌جای meta refresh اجباری

### متخصص
- برچسب ناوبری/عنوان «برنامه من» به‌جای «میز پذیرش»
- توضیح محدودیت: صف و تأیید آنلاین با پذیرش است

### همزمانی / امنیت عملیاتی
- `lockForUpdate` + idempotency روی offer/decline پیگیری
- پیام تعارض موجود برای تأیید/رد/چک‌این حفظ و مسیر redirect شفاف شد
- جستجوی پرونده‌های هم‌موبایل از جزئیات درخواست آنلاین (بدون auto-merge)

## آنچه عمداً ساخته نشد

Billing، SMS/OTP، درگاه پرداخت، WebSocket، portal بیمار، multi-clinic، `dental_os_test` (نیاز به دسترسی ادمین MariaDB).

---

## دستورها و نتایج واقعی

| دستور | نتیجه |
|-------|--------|
| `php artisan test` | **79 passed / 1 skipped / 480 assertions / ~33s** |
| `npm run build` | موفق؛ 61 ماژول |
| Visual QA | صفحه اصلی، `/book` (empty state)، `/login`؛ emulation عرض با CDP ناپایدار بود |

---

## کار بعدی (Day 9 پیشنهادی)

1. ایجاد `dental_os_test` و فعال‌سازی concurrency InnoDB.  
2. یا Billing پایه حضوری (Invoice/Payment/Balance) بدون درگاه آنلاین.  
3. یا seed امن خدمات bookable برای محیط محلی اگر خالی باشد.
