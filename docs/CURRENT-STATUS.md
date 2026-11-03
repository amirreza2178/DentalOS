# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-03  
فاز: Day 10 — Acceptance & Operational Readiness  
پیش از آن: Day 9 — Core Experience Excellence

---

## فاز و مایلستون

Day 1 تا Day 5: `2f16bc5e85a73344a1b71a93a31f428bba85859d`  
Day 6: `8624aa99b204e7d6f67fd320026aafcc39b411c0`  
Day 7: `67fa2d8b10683fbe0badd51a96fbeef28bfe2560`  
Day 8: `a5b88af`  
Day 9: `b0a7860`  
Day 10: `7340da6`

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
| `test_dental_os` | suite همزمانی InnoDB با `MARIADB_CONCURRENCY_TEST=1` |
| PHPUnit پیش‌فرض | sqlite `:memory:` |

---

## آنچه در Day 10 پیاده شد

### منطقه زمانی Asia/Tehran (تأیید محصول)
- `APP_TIMEZONE=Asia/Tehran` در `.env.example`، `phpunit.xml` و پیش‌فرض `config/app.php`
- استراتژی واحد: ساعت دیوار نوبت/شیفت در timezone برنامه ذخیره و نمایش داده می‌شود (بدون تبدیل پراکنده)
- `ClinicDay` و برچسب‌های UI به‌روز شدند
- `clinic_settings.timezone` در bootstrap محلی به `Asia/Tehran` هم‌تراز می‌شود
- تست مرز روز و ثبات offset بدون DST پس از ۱۴۰۱/۲۰۲۲

### پذیرش و UX
- دلیل رد درخواست آنلاین اجباری (Form Request + Action)
- میانبر «رد با دلیل» و «جابه‌جایی» روی کارت موبایل مرکز فرمان
- فهرست نوبت‌ها با مرز نیمه‌باز `ClinicDay` به‌جای `whereDate` خام

### امنیت bootstrap
- `dentalos:bootstrap-local-ops`: فقط `APP_ENV=local`، host لوپ‌بک، allowlist پایگاه، تأیید `SELECT DATABASE()`، بدون بازنویسی رمز کاربران موجود، بدون چاپ secret

### پذیرش عملیاتی (بدون افشای PII)
- درایور محلی Feature/HTTP: رد آنلاین با دلیل، ثبت بیمار، نوبت داخلی، جابه‌جایی، پیگیری، مرکز فرمان، برنامه متخصص، طرح درمان، وظیفه، به‌روزرسانی وضعیت، انکار جستجوی پذیرش برای متخصص

## آنچه عمداً ساخته نشد

Billing، SMS/OTP، درگاه پرداخت، WebSocket، portal بیمار، multi-clinic، hosting/deploy.

---

## دستورها و نتایج واقعی

| دستور | نتیجه |
|------|--------|
| `php artisan test` | **91 passed / 2 skipped / 521 assertions** |
| `MARIADB_CONCURRENCY_TEST=1` + `test_dental_os` | **2 passed** |
| `npm run build` | موفق |
| Visual QA عمومی | موبایل/تبلت/دسکتاپ برای `/book` و اسلات‌ها |
| Browser ورود کارکنان | **Blocked**: تزریق cookie نشست CLI در مرورگر تعبیه‌شده احراز هویت نشد؛ عمداً سیاست امنیتی برای تسهیل تست تضعیف نشد |

### تست‌های skipped (بدون فلگ همزمانی)
1. `Tests\Feature\Clinical\MariaDbConcurrencyGateTest::test_dedicated_mariadb_concurrency_database_is_available_or_explicitly_blocked` — نیاز به `MARIADB_CONCURRENCY_TEST=1`
2. `Tests\Feature\Clinical\MariaDbConcurrencyGateTest::test_innodb_two_processes_compete_for_one_slot_and_only_one_reserves` — همان شرط

با فلگ و `test_dental_os` هر دو **resolved/passed**.

---

## کار بعدی پیشنهادی

1. Billing پایه حضوری (Invoice/Payment/Balance) بدون درگاه آنلاین.  
2. مرورگر واقعی کارکنان با ورود دستی امن (بدون ضعیف‌کردن auth).  
3. یا portal بیمار پس از تصمیم پیامک.
