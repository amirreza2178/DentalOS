# قوانین کسب‌وکار DentalOS

تاریخ: 2026-11-02  
هر قانون یا Confirmed است یا Assumption/Provisional برچسب خورده.

---

## BR-01 — مسئولیت بالینی
**Confirmed.** متخصص ارتودنسی مسئول معاینه و طرح درمان است. سیستم تشخیص، تجویز، یا تصمیم بالینی خودکار نمی‌دهد.

## BR-02 — تفکیک موجودیت‌ها
**Confirmed.** Appointment، PatientVisit، TreatmentPlan/Task، WaitingQueueEntry قابل جایگزینی با یکدیگر نیستند.

## BR-03 — نقش کادر بالینی
**Confirmed.** کادر بالینی ممکن است دانشجو یا نیروی واجد شرایط باشد؛ مجوز و نقش جدا از «دندان‌پزشک دارای پروانه» مدل می‌شود.

## BR-04 — منابع configurable
**Confirmed.** تعداد صندلی/یونیت و تعداد کارکنان همزمان hardcode نمی‌شود. نیاز منابع از تعریف خدمت خوانده می‌شود.

## BR-05 — مدت خدمت
**Confirmed.** مدت نوبت از `treatment_services.default_duration_minutes` (و override مجاز) می‌آید؛ یک ساعت پیش‌فرض اجباری نیست.

## BR-06 — موتور availability یکسان
**Confirmed.** رزرو آنلاین و پذیرش از یک مجموعه قواعد availability و reservation استفاده می‌کنند.

## BR-07 — ضد تداخل منابع
**Confirmed.** برای منابع required، همپوشانی زمانی در وضعیت‌های اشغال‌کننده مجاز نیست. ایجاد رزرو در transaction با قفل مناسب انجام می‌شود.

## BR-08 — تأیید رزرو
**Confirmed.** حالت تأیید `immediate` یا `receptionist_approval` در سطح کلینیک و قابل override در خدمت است.

## BR-09 — Follow-up خاموش ممنوع
**Confirmed.** Follow-up پیشنهادی هرگز بدون تأیید بیمار یا اقدام مجاز پذیرش به Appointment confirmed تبدیل نمی‌شود.

## BR-10 — حریم پرتال بیمار
**Confirmed.** بیمار فقط داده خودش را می‌بیند.

## BR-11 — least privilege و notes
**Confirmed.** پذیرشگر به‌صورت پیش‌فرض به clinical notes حساس دسترسی نامحدود ندارد.

## BR-12 — پرداخت MVP
**Confirmed.** فقط ثبت حضوری charge/payment/balance؛ بدون درگاه آنلاین.

## BR-13 — تاریخ
**Confirmed.** ذخیره datetime استاندارد؛ نمایش جلالی و RTL فارسی در UI.

## BR-14 — Audit
**Confirmed.** تغییرات مهم عملیاتی باید قابل ردیابی باشند.

---

## قوانین وضعیت Appointment (Provisional تا تأیید کلینیک)

اشغال‌کننده منابع: `pending_approval`, `confirmed`, `checked_in`, `in_progress`  
غیر اشغال‌کننده: `draft`, `cancelled`, `no_show`, `completed`, `rescheduled`

انتقال‌های مجاز: مطابق PRODUCT-REQUIREMENTS §4.1.

## قوانین صف (Provisional)
- ورود به صف معمولاً با check-in.
- یک Visit حداکثر یک صف باز فعال دارد.
- تکمیل Visit صف باز را می‌بندد.

## قوانین مالی (Provisional)
- Balance محاسبه‌ای است.
- مبلغ منفی پرداخت مجاز نیست.
- حذف فیزیکی پرداخت ممنوع؛ اصلاح با رکورد تعدیلی یا void کنترل‌شده.

## قوانین همزمانی (Provisional فنی)
- ReservationService همه allocations را در یک DB transaction می‌سازد.
- در تعارض overlap، کل عملیات fail می‌شود و پیام امن به کاربر برمی‌گردد.

---

## آنچه قانون نیست (عمداً)

- الزام حضور همزمان هر شش کادر برای هر نوبت
- یکسان فرض‌کردن همه صندلی‌ها در آینده بدون capability
- تبدیل خودکار پیشنهاد سیستم به نوبت قطعی
- دسترسی کامل پذیرش به تمام محتوای بالینی
