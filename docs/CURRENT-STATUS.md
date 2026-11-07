# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-07  
فاز: Day 12 — Reception UX + isolated demo (**READY** برای دمو محلی)

---

## Git

- شاخه: `master` @ `7dcb93b`
- پیام: `feat: day 12 reception UX and isolated demo environment`
- هم‌تراز با `origin/master` پس از push این بسته
- بدون secrets در مسیر commit (`.env` و `demo-credentials.txt` gitignored)

---

## Day 12 — بسته‌شده

- فشرده‌سازی UX نوبت داخلی/جابه‌جایی (sticky patient، بازیابی conflict، میان‌بر ساعت)
- شفافیت سقف ۸تایی آنلاین/پیگیری + شمارش باقیمانده + CTA فهرست کامل
- `data-booking-submit` روی چک‌این و اقدامات پرتکرار میز
- دستور ایزوله `dentalos:prepare-demo` → فقط `dental_os_demo`
- اسکریپت دمو: `docs/DAY12-DEMO.md`
- تست‌های متمرکز Day 12: **12 passed / 55 assertions**
- Browser QA authenticated (desktop + mobile 390×844، پذیرش + متخصص): **PASS**
- Seed دمو روی `dental_os_demo`: **OK** (پس از GRANT اپراتور)

---

## شواهد تست

| منبع | نتیجه |
|------|--------|
| Day 12 feature + safety | **12 passed / 55 assertions** |
| Browser QA authenticated | **PASS** — اسکریپت ۵ دقیقه‌ای روی `dental_os_demo` |
| `dental_os_demo` seed | **OK** — identity gate؛ `dental_os` دست نخورده |

---

## کار بعدی

1. دمو زنده با `docs/DAY12-DEMO.md` و حساب‌های synthetic
2. Day 13 / اولویت محصول بعدی (PO)
3. قبل از هر محیط غیرمحلی: rotate رمزهای دمو
