# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-03  
فاز: Day 9 — Core Experience Excellence (بیمار + پذیرش + متخصص)  
پیش از آن: Day 8 — Operational Excellence و تجربه کاربری end-to-end

---

## فاز و مایلستون

Day 1 تا Day 5: `2f16bc5e85a73344a1b71a93a31f428bba85859d`  
Day 6: `8624aa99b204e7d6f67fd320026aafcc39b411c0`  
Day 7: `67fa2d8b10683fbe0badd51a96fbeef28bfe2560`  
Day 8: `a5b88af`  
Day 9: `b0a7860`

اپ محلی به **MariaDB** وصل است: پایگاه `dental_os`، کاربر `dental_os_app`.

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
| `dental_os` | داده عملیاتی محلی؛ `migrate:fresh` اجرا نشد |
| `dental_os_test` | **ایجاد توسط کاربر اپ ممکن نیست** (Access denied) |
| `test_dental_os` | **ایجاد شد** با GRANT عمومی `test_%`؛ migration جداگانه اجرا شد |
| concurrency InnoDB | با `MARIADB_CONCURRENCY_TEST=1` و `MARIADB_CONCURRENCY_DATABASE=test_dental_os` **۲ تست پاس** |
| PHPUnit پیش‌فرض | sqlite `:memory:` |

---

## آنچه در Day 9 پیاده شد

### راه‌اندازی محلی امن
- دستور `php artisan dentalos:bootstrap-local-ops` فقط در `local`
- یونیت، شیفت صبح شنبه–پنجشنبه، خدمت‌های bookable (`CONS`/`ADJ`) + خدمت داخلی غیرآنلاین
- حساب‌های پذیرش×۲، متخصص، کادر بالینی؛ رمز فقط در `storage/app/private/` (gitignored)

### بیمار / رزرو
- ریشه empty state روز ۸: پایگاه بدون خدمت bookable بود (نه باگ فیلتر)
- empty state شفاف با مسیر تماس/بازگشت
- خدمت‌ها با CTA «ادامه ← انتخاب تاریخ»
- تایپوگرافی مشترک `dos-title-*` / `dos-body` روی مسیر رزرو
- E2E محلی: درخواست → `pending_approval` → بدون confirmed تصادفی؛ confirmation بدون PII

### پذیرش
- soft-refresh دیگر پیش‌نویس فرم (از جمله دلیل رد) را پاک نمی‌کند
- زمان انتظار صف به دقیقه صحیح گرد می‌شود
- تأیید آنلاین، چک‌این و صف روی مرکز فرمان در مرورگر تأیید شد

### متخصص
- کارت «اقدام بالینی بعدی» روی برنامه امروز
- خلاصه آماری محدود به نوبت خودش (بدون آمار صف/آنلاین پذیرش)
- جستجوی پذیرش برای متخصص ممنوع (سرور-side)

### همزمانی
- suite InnoDB واقعی روی `test_dental_os` با دو فرایند و فقط یک برنده

## آنچه عمداً ساخته نشد

Billing، SMS/OTP، درگاه پرداخت، WebSocket، portal بیمار، multi-clinic.

---

## دستورها و نتایج واقعی

| دستور | نتیجه |
|-------|--------|
| `php artisan test` | **85 passed / 2 skipped / 498 assertions / ~24s** |
| `MARIADB_CONCURRENCY_TEST=1` + `test_dental_os` | **2 passed** (gate + InnoDB race) |
| `npm run build` | موفق |
| Visual QA | صفحه اصلی، `/book` با خدمت، تاریخ/اسلات/فرم/confirmation، مرکز فرمان پذیرش (موبایل)؛ ورود متخصص در مرورگر اصطکاک داشت و با HTTP داخلی verify شد |

---

## کار بعدی (Day 10 پیشنهادی)

1. Billing پایه حضوری (Invoice/Payment/Balance) بدون درگاه آنلاین.  
2. یا تکمیل QA مرورگر دسکتاپ/تبلت با emulation پایدارتر.  
3. یا ایجاد رسمی `dental_os_test` با ادمین MariaDB اگر نام دقیق‌تر از `test_dental_os` لازم باشد.
