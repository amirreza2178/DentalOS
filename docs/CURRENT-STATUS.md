# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-02

---

## فاز و مایلستون فعلی

**فاز:** Day 1 — Environment audit + Product/Architecture docs + Laravel foundation  
**مایلستون:** تکمیل foundation قابل ادامه برای Day 2 (Identity/Patients/Settings)  
**وضعیت مایلستون:** انجام‌شده از نظر مستندات و اسکلت اپ؛ اتصال MariaDB اپ هنوز verify نشده

---

## نسخه‌های محیط تأییدشده

| ابزار | نتیجه واقعی |
|-------|-------------|
| Workspace اولیه | خالی بود (0 فایل) قبل از نصب |
| PHP | 8.4.24 (Herd php84) |
| Composer | 2.10.2 |
| Node | v26.7.0 |
| npm | 11.19.0 |
| Git | 2.55.0.windows.1 |
| Laravel Framework | 13.39.0 (`php artisan --version` / `artisan about`) |
| PHPUnit (پروژه) | ^12.5.12 — اجرا شد |
| MariaDB client/server | 11.4.12 — سرویس Windows `MariaDB` = Running، پورت 3306 = باز |
| اتصال PDO root با رمز خالی | **FAILED**: `SQLSTATE[HY000] [2054] ... auth_gssapi_client` |
| sqlite3 CLI در PATH | یافت نشد |
| PHP `pdo_sqlite` / `pdo_mysql` | هر دو موجود |
| Tailwind/Vite | Vite 7.3.7 build موفق |

---

## فایل‌های ایجاد/تغییر یافته در این مأموریت

### ایجاد شده
- کل اسکلت Laravel 13 در ریشه پروژه (نه در زیرپوشه تودرتو)
- `docs/PROJECT-BRIEF.md`
- `docs/PRODUCT-REQUIREMENTS.md`
- `docs/ARCHITECTURE.md`
- `docs/DATABASE-DESIGN.md`
- `docs/BUSINESS-RULES.md`
- `docs/IMPLEMENTATION-PLAN.md`
- `docs/DECISIONS.md`
- `docs/CURRENT-STATUS.md` (همین فایل)
- `.git/` با `git init` (پس از اینکه create-project بدون VCS مانده بود)
- `node_modules/` و `package-lock.json` از `npm install`
- `public/build/*` از `npm run build` (gitignore شده)

### تغییر یافته نسبت به اسکلت
- `.env.example` — نام DentalOS، locale فا، یادداشت MariaDB، `APP_TIMEZONE`
- `config/app.php` — `timezone` از `env('APP_TIMEZONE', 'UTC')`
- `README.md` — معرفی DentalOS و لینک مستندات

### عمداً دست‌نخورده
- `.env` موجود overwrite نشد
- migration دامنه کسب‌وکار ساخته نشد
- داده جعلی بیمار/آمار ساخته نشد

---

## دستورات اجراشده و نتیجه واقعی

| دستور | نتیجه |
|-------|--------|
| لیست workspace اولیه | Count=0 خالی |
| `php -v` | PHP 8.4.24 OK |
| `composer -V` | 2.10.2 OK |
| `node -v` / `npm -v` | v26.7.0 / 11.19.0 OK |
| `git --version` | 2.55.0 OK |
| سرویس MariaDB | Running Automatic |
| `Test-NetConnection 127.0.0.1:3306` | TcpTestSucceeded True |
| `composer show laravel/laravel --all` | نسخه 13.x از جمله v13.11.0+ در دسترس؛ framework ^13 |
| `composer create-project laravel/laravel:^13.0 .` (اول) | FAIL: directory not empty به‌خاطر فایل موقت چک DB |
| جابه‌جایی فایل موقت + create-project مجدد | SUCCESS — Laravel در ریشه نصب شد |
| `php artisan --version` | Laravel Framework 13.39.0 |
| PDO connect root/empty | CONNECT_FAILED auth_gssapi_client |
| `git init` | empty repo initialized |
| `npm install` | 87 packages، 0 vulnerabilities |
| `php artisan test` | **2 passed** (Unit+Feature مثال) |
| `npm run build` | SUCCESS |
| `git check-ignore .env` | `.gitignore:3:.env` — محافظت می‌شود |

---

## تست‌های خودکار

```
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\ExampleTest
Tests: 2 passed (2 assertions)
```

هیچ تست دامنه کسب‌وکار هنوز نوشته نشده (عمدی).

---

## Blockerها و سؤالات باز

### Blockerها
1. **احراز هویت MariaDB محلی تأیید نشد.** سرویس بالاست ولی اتصال با root/رمزخالی به‌خاطر `auth_gssapi_client` شکست خورد. بدون credential معتبر از مالک محیط، اپ روی MariaDB پیکربندی قطعی نمی‌شود.
2. GitHub remote هنوز ساخته/تنظیم نشده.
3. Commit اولیه هنوز زده نشده (طبق دستور: اول نمایش status به کاربر).

### سؤالات باز برای کلینیک/ذینفع
- تعداد و قابلیت دقیق یونیت‌ها
- ساعات کاری/تعطیلات
- آیا متخصص برای همه انواع نوبت لازم است؟
- سیاست no-show و مالی پیش‌پرداخت
- سطح دسترسی پذیرش به خلاصه طرح درمان
- ارز و timezone نهایی
- کانال اطلاع‌رسانی (پیامک) در MVP؟

---

## ریسک‌های معماری باقی‌مانده

- پیاده‌سازی overlap prevention روی MariaDB بدون EXCLUDE constraint
- خطر نشت داده بالینی به نقش پذیرش اگر Policyها ضعیف پیاده شوند
- اختلاف رفتار sqlite تست در برابر MariaDB برای قفل‌ها — نیاز به حداقل یک تست integration روی MariaDB قبل از تولید

---

## کار بعدی دقیق پیشنهادی (Day 2 شروع)

1. دریافت/ساخت کاربر MariaDB سازگار با PDO و verify واقعی اتصال؛ تنظیم `.env` محلی بدون commit.
2. Migrationهای Identity: roles، staff_profiles، patients، clinic_settings، chairs، treatment_services.
3. Layout Blade RTL فارسی + تنظیم locale/timezone.
4. Policies پایه و تست Feature ایجاد بیمار + ممنوعیت مشاهده متقابل در پرتال (اسکلت auth بیمار).

**Commit/Push:** انجام نشود تا کاربر status و پیشنهاد را تأیید کند.
