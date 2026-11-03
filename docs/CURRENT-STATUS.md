# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-03  
فاز: Day 7 — هویت کلینیک + مرکز فرمان پذیرش + سخت‌سازی امنیتی

---

## فاز و مایلستون

Day 1 تا Day 5: `2f16bc5e85a73344a1b71a93a31f428bba85859d`  
Day 6: `8624aa99b204e7d6f67fd320026aafcc39b411c0`  
Day 7: `67fa2d8b10683fbe0badd51a96fbeef28bfe2560`

اپ محلی به **MariaDB** وصل است: پایگاه `dental_os`، کاربر `dental_os_app@127.0.0.1`.

---

## Git

- شاخه: `master`
- Remote: `origin` = `https://github.com/amirreza2178/DentalOS.git`
- Push Day 7: انجام شد؛ شاخه با `origin/master` هم‌تراز است
- `.env` و `storage/app/private/*` در `.gitignore` هستند

---

## MariaDB

| مورد | نتیجه |
|------|--------|
| اتصال Laravel | `pdo=ok` / `driver=mariadb` |
| migrate:status | بدون migration جدید Day 7 (کلیدهای هویت روی `clinic_settings` موجود) |
| `dental_os_test` | **وجود ندارد** — تست همزمانی InnoDB مسدود و مستند شد |
| `migrate:fresh` | اجرا نشد |

PHPUnit از `sqlite :memory:` استفاده می‌کند.

---

## آنچه در Day 7 پیاده شد

### هویت مرکزی کلینیک
- `App\Support\ClinicIdentity` + کلیدهای جدید در `ClinicSetting` (بدون migration جدید)
- نام نمایشی پزشک پیش‌فرض: **دکتر هومان شفاعی**
- عنوان: متخصص ارتودنسی
- نام کلینیک پیش‌فرض: کلینیک ارتودنسی دکتر هومان شفاعی
- صفحات عمومی رزرو با هویت تایپوگرافی (بدون لوگو/پرتره تأییدنشده)

### مرکز فرمان پذیرش
- `/reception` به command center روزانه تبدیل شد
- نوبت‌های امروز، صف (waiting/called/in_service)، درخواست آنلاین، پیگیری، هشدارها، نوبت‌های پیش‌رو
- جستجوی سریع، تأیید/رد آنلاین در همان صفحه، تازه‌سازی خودکار ۹۰ثانیه‌ای بدون WebSocket
- کارت‌های موبایل + جدول دسکتاپ

### امنیت
- چرخش رمز موقت ضعیف `staff@dentalos.local` به credential قوی محلی (فقط در فایل gitignored خصوصی)
- صفحه confirmation عمومی: حداقل داده، بدون نام/موبایل بیمار، 404 عمومی برای UUID نامعتبر
- محدوده بیمار متخصص + تست‌های deny
- رویداد عملیاتی برای لغو/جابه‌جایی نوبت
- `lockForUpdate` روی تغییر وضعیت وظیفه درمانی
- CSP: `style-src 'unsafe-inline'` هنوز لازم است؛ honestly documented
- تست اختصاصی MariaDB concurrency: skip تا ساخت `dental_os_test`

## آنچه عمداً ساخته نشد

Billing، SMS/OTP، درگاه پرداخت، CMS برندینگ پیچیده، multi-clinic، WebSocket، دانلود عکس پزشک، آمار جعلی.

---

## دستورها و نتایج واقعی

| دستور | نتیجه |
|-------|--------|
| `php artisan test` | **73 passed / 1 skipped / 441 assertions / ~22s** |
| `npm run build` | موفق؛ 61 ماژول |
| Password rotation | انجام شد؛ رمز در گزارش چاپ نشد |
| Visual QA | مرورگر عمومی + اندازه صفحه؛ authenticated در صورت امکان |

---

## کار بعدی (Day 8 پیشنهادی)

Billing پایه (Invoice/Payment/Balance) + گزارش مالی روزانه پذیرش؛ بدون درگاه آنلاین. یا تکمیل `dental_os_test` برای concurrency InnoDB.
