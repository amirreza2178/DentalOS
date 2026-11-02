# DentalOS

سیستم مدیریت کلینیک دندان‌پزشکی / ارتودنسی (نام کاری).  
پشته: Laravel 13، Blade، Tailwind CSS، Vite، هدف پایگاه‌داده MariaDB، تست با SQLite in-memory.

## مستندات

- [docs/PROJECT-BRIEF.md](docs/PROJECT-BRIEF.md)
- [docs/PRODUCT-REQUIREMENTS.md](docs/PRODUCT-REQUIREMENTS.md)
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- [docs/DATABASE-DESIGN.md](docs/DATABASE-DESIGN.md)
- [docs/BUSINESS-RULES.md](docs/BUSINESS-RULES.md)
- [docs/IMPLEMENTATION-PLAN.md](docs/IMPLEMENTATION-PLAN.md)
- [docs/CURRENT-STATUS.md](docs/CURRENT-STATUS.md)
- [docs/DECISIONS.md](docs/DECISIONS.md)

## راه‌اندازی محلی (خلاصه)

```bash
composer install
copy .env.example .env   # اگر .env وجود ندارد
php artisan key:generate
npm install
npm run build
php artisan serve
```

برای توسعه اپ، MariaDB را با credential واقعی پیکربندی کنید. فایل `.env` را commit نکنید.

```bash
php artisan test
```

## وضعیت

Day 1: foundation و مشخصات. ماژول‌های کسب‌وکار هنوز پیاده‌سازی نشده‌اند. جزئیات در `docs/CURRENT-STATUS.md`.
