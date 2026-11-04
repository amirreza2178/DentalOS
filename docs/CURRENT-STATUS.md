# وضعیت جاری DentalOS

آخرین به‌روزرسانی: 2026-11-04  
فاز: Unified Jalali Calendar — **QA نهایی انجام شد؛ آماده تأیید commit** (هنوز commit/push نشده)

---

## Git

- شاخه: `master` @ `9be98a9` (Day 11 concurrency hardening) + working tree تقویم جلالی
- جلوتر از `origin/master` به‌اندازه ۲ commit
- بدون secrets در مسیر commit
- Push انجام نشده

---

## Day 11 — commit شده (verified)

- Hash: `9be98a9` — `feat: day 11 concurrency hardening for cancel, check-in, and races`

---

## تقویم جلالی — پس از QA

- کامپوننت مشترک `x-jalali-date-field`
- `dayStatusesBetween` با batch holiday/shift + catalog cache + short-circuit `hasOpening`
- a11y: `role=region` + `hidden` روی پنل popover
- JS parity با جفت‌های شناخته‌شده: OK

---

## شواهد تست

| منبع | نتیجه |
|------|--------|
| `npm run build` | OK |
| JS Jalali parity (۸ جفت + leap) | OK |
| `php artisan test` | **121 passed / 7 skipped / 672 assertions** |
| Browser/device walkthrough | **NOT VERIFIED** |

---

## کار بعدی

1. تأیید صریح commit تقویم
2. Browser checklist (موبایل/دسکتاپ)
3. (اختیاری) push پس از commit
