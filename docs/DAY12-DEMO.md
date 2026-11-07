# DentalOS — Day 12 Demo Pack

Synthetic-only demonstration guide. No real patient data. Do not display passwords on screen.

## Safety

| Rule | Detail |
|------|--------|
| Demo database | `dental_os_demo` only |
| Forbidden | `dental_os`, production/staging names, `migrate:fresh` on non-demo |
| Command | `php artisan dentalos:prepare-demo` |
| Reset | `--reset --i-understand-demo-reset` after identity verification |
| Credentials | `storage/app/private/demo-credentials.txt` (gitignored) |

## Prepare (once per machine)

1. On local MariaDB loopback, as a privileged admin (not the least-privilege app user), create the isolated DB and grants:

```sql
CREATE DATABASE IF NOT EXISTS dental_os_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON dental_os_demo.* TO 'dental_os_app'@'localhost';
FLUSH PRIVILEGES;
```

2. From project root (APP_ENV=local). The command forces the `demo` connection to `dental_os_demo` and refuses any other identity:

```bash
php artisan dentalos:prepare-demo --reset --i-understand-demo-reset
```

3. Point the running app at the demo DB (local only):

```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_DATABASE=dental_os_demo
```

4. Restart `php artisan serve` / Vite if needed. Read credentials from the private file off-camera.

5. Demo accounts (synthetic):
   - Reception: `demo.reception@dentalos.demo`
   - Orthodontist: `demo.ortho@dentalos.demo`

### Status (2026-11-07)

Local demo DB provisioning completed after operator grants. Seed and Browser QA on `dental_os_demo` are **READY**. Do not point seeding at `dental_os`.

## Five-minute script

| Min | Step | Route | Account | Action | Expected | Recovery |
|-----|------|-------|---------|--------|----------|----------|
| 0:00 | Command center | `/reception` | Reception | Land | Jalali day, tiles, alerts, queue, online/follow-up panels | Soft refresh / «تازه‌سازی» |
| 0:40 | Find patient | Search on day or `/reception/search` | Reception | Search `DEMO-P` / `آناهیتا` | Synthetic hit; «نوبت جدید» deep-link | Create via `/patients/create` only if needed |
| 1:20 | Internal book | `/appointments/create?patient_id=…` | Reception | Service + Jalali date → pick free slot → confirm | Sticky patient; slots from AvailabilityService; show page confirmation | On conflict, service/date preserved; pick another slot |
| 2:20 | Online approve | Day «درخواست‌های آنلاین» or `/booking-requests` | Reception | Approve pending | Status → confirmed | Reject with required reason; resources stay locked until decision |
| 3:10 | Check-in + queue | Day schedule | Reception | Check-in → call next → start service | Queue waiting → called → in service; button disables on submit | Idempotent check-in if double-clicked |
| 4:10 | Orthodontist | Logout → `/reception` | Ortho | Open day | Scoped «برنامه امروز من»; no queue/online operate | Confirm role middleware if full desk appears |

## Implemented vs roadmap

**Demo these (implemented):** reception command center, patient search, internal book/reschedule, online approve/reject, check-in, queue, specialist scoped day, Jalali calendar, Asia/Tehran day model.

**Do not claim:** SMS/OTP, billing/payments, AI diagnosis, multi-clinic tenancy, patient portal, hosting.

## Reset between demos

```bash
php artisan dentalos:prepare-demo --reset --i-understand-demo-reset
```

Stops if `SELECT DATABASE()` is not exactly `dental_os_demo`.
