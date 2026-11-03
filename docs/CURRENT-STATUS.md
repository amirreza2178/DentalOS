# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-03  
فاز: Reception UX Comfort Pass (پس از Day 10)  
پیش از آن: Day 10 — Acceptance & Operational Readiness

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

## آنچه در Reception UX Comfort Pass پیاده شد

### ناوبری نقش‌آگاه پذیرش
- مسیر روزانه پذیرش: مرکز فرمان، بیماران، نوبت‌ها، درخواست‌های آنلاین، پیگیری‌ها، هفته
- موارد کمترروزانه (متخصصان، کادر، یونیت، خدمات، برنامه‌/وظایف درمانی، میز کار) زیر «مدیریت / تنظیمات»
- ادمین همچنان گروه مدیریت غنی‌تر دارد؛ دسترسی متخصص/ادمین شکسته نشد

### مرکز فرمان
- CTA «ثبت بیمار جدید» کنار جستجوی سریع (مسیر واقعی `patients.create`؛ هدف لمسی موبایل حفظ شد)
- «لغو» روی کارت موبایل و جدول دسکتاپ با همان Policy/`CancelAppointment` و confirm موجود؛ `return_to=reception.day` بدون تضعیف CSRF/authz
- درخواست آنلاین معلق به‌صورت پیش‌فرض فقط برای روز جلالی انتخاب‌شده؛ لینک «همه روزها»
- حذف ردیف‌های تکراری پیگیری برای همان بیمار + خدمت + وضعیت

## آنچه عمداً ساخته نشد

Billing، SMS/OTP، درگاه پرداخت، WebSocket، portal بیمار، multi-clinic، hosting/deploy.

---

## دستورها و نتایج واقعی

| دستور | نتیجه |
|------|--------|
| `php artisan test` | **96 passed / 2 skipped / 561 assertions** |
| `ReceptionCommandCenterTest` | **8 passed** |
| Browser پذیرش روی `/reception` | **OK** با نشست محلی موجود: CTA ثبت بیمار، لغو، «همه روزها»، ناوبری اولیه + «مدیریت / تنظیمات» |
| `migrate:fresh` | اجرا نشد |

### تست‌های skipped (بدون فلگ همزمانی)
1. `Tests\Feature\Clinical\MariaDbConcurrencyGateTest::test_dedicated_mariadb_concurrency_database_is_available_or_explicitly_blocked` — نیاز به `MARIADB_CONCURRENCY_TEST=1`
2. `Tests\Feature\Clinical\MariaDbConcurrencyGateTest::test_innodb_two_processes_compete_for_one_slot_and_only_one_reserves` — همان شرط

---

## کار بعدی پیشنهادی

1. Billing پایه حضوری (Invoice/Payment/Balance) بدون درگاه آنلاین.  
2. اصطکاک باقی‌مانده پذیرش: reason اجباری برای لغو (فعلاً فقط confirm)، و فیلتر روز برای پیگیری‌ها.  
3. یا portal بیمار پس از تصمیم پیامک.
