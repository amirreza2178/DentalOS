# برنامه پیاده‌سازی DentalOS (پنجره حدوداً ۸ روزه)

تاریخ به‌روزرسانی: 2026-11-02

---

## نقشه روزها (پیشنهادی)

### Day 1 — Foundation و مشخصات ✅ (در حال تکمیل)
- ممیزی محیط
- مستندات محصول/معماری/داده
- نصب Laravel 13 در ریشه پروژه
- Git init، npm install، پیکربندی تست SQLite
- بدون migration دامنه زودرس

### Day 2 — هویت، نقش‌ها، بیمار، تنظیمات پایه
- مدل User/Role/StaffProfile/Patient
- Policies اولیه
- UI فارسی RTL پایه (layout)
- ClinicSettings و Chairs/Services CRUD حداقلی ادمین

### Day 3 — موتور Scheduling
- شیفت/تعطیلات
- AvailabilityService
- Appointment + ResourceAllocation
- تست ضد double-booking و concurrency

### Day 4 — میز پذیرش
- تقویم روزانه/هفتگی
- رزرو داخلی سریع
- Check-in، Visit، Waiting Queue
- جستجوی بیمار بهینه

### Day 5 — پرتال بیمار و سیاست تأیید
- ثبت‌نام/ورود بیمار
- مشاهده اسلات و رزرو آنلاین
- مسیر pending_approval
- جداسازی داده self-only

### Day 6 — بالینی سبک + Follow-up
- TreatmentPlan/Task
- تخصیص وظایف به کادر
- Follow-up پیشنهادی با تبدیل امن به نوبت

### Day 7 — Billing و Audit و گزارش پایه
- Invoice/Payment/Balance
- ActivityLog رویدادهای مهم
- گزارش روزانه پذیرش (نوبت‌ها، no-show، دریافت‌ها)

### Day 8 — سخت‌سازی کیفیت
- تست پذیرش معیارهای MVP
- رفع باگ RTL/جلالی
- آماده‌سازی استقرار MariaDB
- پاکسازی مستندات وضعیت

---

## ترتیب فنی پیشنهادی ماژول‌ها

1. Auth/Roles  
2. Patients  
3. Catalog + Resources  
4. Scheduling core  
5. Reception UX  
6. Patient portal  
7. Clinical light  
8. Billing  
9. Audit/Reports  

---

## تعریف Done برای هر ماژول

- Migration + Model + Policy/Request  
- مسیر UI یا API لازم  
- تست Feature حداقل مسیر خوشحال + یک شکست مهم  
- به‌روزرسانی CURRENT-STATUS و در صورت تصمیم جدید DECISIONS  

---

## کار بعدی دقیق پس از Day 1

1. گرفتن credential معتبر MariaDB از مالک محیط یا ساخت کاربر محلی غیر GSSAPI و verify اتصال.
2. تنظیم `.env` اپ برای MariaDB بدون commit کردن اسرار (فعلاً اسکلت روی sqlite پیش‌فرض Laravel است).
3. پیاده‌سازی Enum نقش‌ها + migrationهای Identity/Patient/Settings/Chairs/Services.
4. layout Blade RTL فارسی + نمایش جلالی پایه.
5. اولین تست‌های Feature برای Patient create و ممنوعیت دسترسی متقابل.

---

## چیزهایی که عمداً به تعویق افتاده‌اند

- همه migrationهای دامنه
- seed داده جعلی آماری
- درگاه پرداخت
- SPA
- multi-tenancy
