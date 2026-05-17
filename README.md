# Pickle Ballan ni Juan — Court Reservation & Management System

A Laravel 13 web app for booking pickleball courts, renting equipment, processing manual GCash
payments, handling walk-ins, and running staff check-in/out across multiple branches.

Read the full product brief in `objectives.txt`.

## Stack

- **PHP** 8.3+
- **Laravel** 13
- **Frontend**: Blade + Tailwind 3 + Soft UI Dashboard kit + Alpine.js + Vite
- **Auth**: Laravel Breeze + Spatie Permission
- **Maps**: Leaflet
- **DB**: MySQL 8 / MariaDB 10.6+ recommended for production. SQLite is used for the test suite only.
- **Tests**: Pest 4

## Getting started (local)

```bash
git clone <repo> pickleball
cd pickleball
composer install
cp .env.example .env
php artisan key:generate
# Configure DB_* in .env, then:
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Default seeded accounts (password: `password`):

| Role             | Email                    |
| ---------------- | ------------------------ |
| Super Admin      | superadmin@example.com   |
| Admin            | admin@example.com        |
| Location Manager | manager@example.com      |
| Staff            | staff@example.com        |
| End User         | user@example.com         |

## Daily development

```bash
composer dev    # runs server + queue worker + vite concurrently
composer test   # runs Pest test suite
./vendor/bin/pint  # format PHP files
```

## Scheduled jobs

`routes/console.php` registers four scheduled commands. Run a single dispatcher in production:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

| Command                          | Cadence            | What it does                                                |
| -------------------------------- | ------------------ | ----------------------------------------------------------- |
| `reservations:expire-pending`    | every 10 minutes   | Auto-cancel unpaid reservations past their hold (E10)       |
| `reservations:mark-no-shows`     | hourly             | Flag confirmed reservations whose end time passed (J8)      |
| `reports:aggregate-daily`        | daily 00:30        | Build per-location daily revenue/utilisation rows           |
| `inventory:notify-low-stock`     | daily 07:00        | Notify admins/staff when stock drops to reorder point (D6)  |

## Queue worker

Notifications, mail, and async work go through the `database` queue. Run a worker per box:

```bash
php artisan queue:work --tries=3 --backoff=10
```

Use a process supervisor (systemd, supervisord, or Forge "Daemons") to keep it alive.

## Production deployment checklist

The fastest path is to use the included script. From the server, after a fresh `git pull`:

```bash
bash deploy.sh
```

This installs prod dependencies, builds assets, runs migrations + role seeder,
links storage, caches config/routes/views, and restarts the queue worker.

For first-time setup, also do:

1. **App config**: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `LOG_LEVEL=warning`.
2. **Sessions**: `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`.
3. **Database**: MySQL 8 / MariaDB 10.6+ / PostgreSQL 14+. SQLite is unsafe for concurrent bookings.
4. **Mail**: switch `MAIL_MAILER` from `log` to `smtp`/`ses`/`postmark` so receipt emails actually send.
5. **HTTPS**: TLS at the load balancer / web server. `AppServiceProvider::boot()` already calls
   `URL::forceScheme('https')` for production and proxied requests.
6. **Security headers**: applied automatically via `App\Http\Middleware\SecurityHeaders`.
7. **Cron**: install `deploy/crontab.txt` or run `* * * * * cd /var/www/pickleball && php artisan schedule:run`.
8. **Queue worker**: copy `deploy/supervisor-pickleball.conf` to `/etc/supervisor/conf.d/`.
9. **Backups**: schedule daily DB + uploads backups (e.g. `spatie/laravel-backup`).
10. **Monitoring**: wire `/up` to your health checker; consider Sentry for error tracking.
11. **Payment proof storage**: GCash screenshots are stored on the **private** disk
    (`storage/app/private/payment-proofs/...`) and served through signed URLs that
    expire in 30 minutes. No additional config needed unless moving to S3.

## Module map

| Path                       | What it covers                                                  |
| -------------------------- | --------------------------------------------------------------- |
| `/dashboard`               | Role-aware metrics, calendar, work queue                        |
| `/modules/locations`       | Branch CRUD (admin)                                             |
| `/modules/courts`          | Court CRUD + hide/show/archive (admin)                          |
| `/modules/equipment`       | Inventory CRUD, stock counts (admin)                            |
| `/modules/payments`        | GCash proof upload (customer) + review queue (admin/staff)      |
| `/modules/walk-ins`        | Counter bookings (staff)                                        |
| `/modules/check-ins`       | Staff arrival flow (I1-I5)                                      |
| `/modules/check-outs`      | Session close, damage/late charges (I6-I9)                      |
| `/modules/book-court`      | Customer reservation flow                                       |
| `/modules/receipts`        | Customer receipt list                                           |
| `/modules/reviews`         | Customer ratings                                                |
| `/notifications`           | In-app notification history                                     |
| `/receipts/{reservation}`  | Printable digital receipt                                       |
| `/profile`                 | Account info + photo                                            |
| `/reports/export/{type}`   | CSV export: revenue \| bookings \| cancellations \| no-shows \| equipment |
| `/payments/{id}/proof`     | Signed-URL download of GCash screenshot (private disk)          |

## Roles & permissions

Five Spatie roles, seeded by `RoleSeeder`:

- `super_admin`, `admin` — full management
- `location_manager`, `staff` — assigned-branch operations
- `end_user` — customers

Custom helpers and Spatie middleware (`role`, `permission`, `role_or_permission`) are
wired in `bootstrap/app.php`.

## Tests

```bash
php artisan test
```

The suite uses an in-memory SQLite database. Add new feature tests under `tests/Feature/`.

## License

MIT.
