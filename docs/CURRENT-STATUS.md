# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-07  
فاز: Day 12.1 — Demo env unblocking (**NOT READY** — MariaDB grants؛ commit/push نشده)

---

## Git

- شاخه: `master` @ `92cac52` + working tree Day 12 (uncommitted)
- هم‌تراز با `origin/master` قبل از این تغییرات
- بدون secrets در مسیر commit

---

## Day 12 — انجام‌شده (uncommitted)

- فشرده‌سازی UX نوبت داخلی/جابه‌جایی (sticky patient، بازیابی conflict، میان‌بر ساعت)
- شفافیت سقف ۸تایی آنلاین/پیگیری + شمارش باقیمانده + CTA فهرست کامل
- `data-booking-submit` روی چک‌این و اقدامات پرتکرار میز
- دستور ایزوله `dentalos:prepare-demo` → فقط `dental_os_demo`
- اسکریپت دمو: `docs/DAY12-DEMO.md`
- تست‌های متمرکز Day 12: **12 passed / 55 assertions**

---

## شواهد تست

| منبع | نتیجه |
|------|--------|
| `npm run build` | OK |
| Day 12 feature + safety | **12 passed / 55 assertions** |
| Browser QA authenticated | **BLOCKED** این نشست (بدون خواندن فایل credential) |
| `dental_os_demo` seed | **BLOCKED** (Day 12.1 reconfirmed) — فقط `dental_os` دیده می‌شود؛ 1044 روی CREATE/USE دمو |

---

## کار بعدی

1. ایجاد/اعطای دسترسی MariaDB به `dental_os_demo` سپس `php artisan dentalos:prepare-demo --reset --i-understand-demo-reset`
2. Browser QA احراز هویت‌شده روی دسکتاپ/موبایل با حساب دمو
3. تأیید صریح commit Day 12 از Product Owner
