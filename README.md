# Macarthur Infusions — Booking App

Laravel 13 + Blade monolith for `book.macarthurinfusions.com.au`: customers
pick a service and time slot, pay upfront via Square, and get an instant
confirmation. The nurse manages everything at `/admin`.

The WordPress marketing site stays on the root domain — its "Book an
Appointment" CTAs link here.

## Stack

- **Laravel 13** (PHP 8.4), Blade views, session auth — one deployable
- **Postgres 17** — bookings carry a `tstzrange` **GIST exclusion
  constraint**, so double-booking is impossible at the database layer
- **Tailwind CSS v4 + Preline UI**, themed to the clinic brand
  (Fraunces/Inter, teal/green/orange/cream); Alpine.js for the slot picker
- **Square** Web Payments SDK (client tokenize) + PHP SDK (server charge)
- **Resend** for email in production (`MAIL_MAILER=log` locally)

## Local development

```bash
docker compose up -d         # Postgres 17 on port 54329
composer install && npm install
php artisan migrate:fresh --seed
npm run dev                  # Vite (keep running)
php artisan serve            # http://localhost:8000
php artisan schedule:work    # hold-expiry sweep (separate terminal)
```

Admin login: `macarthurinfusions@outlook.com.au` / `change-me-on-first-login`
(set `ADMIN_INITIAL_PASSWORD` in `.env` before seeding to override).

Square sandbox: create free sandbox credentials at
https://developer.squareup.com, then fill `SQUARE_ACCESS_TOKEN`,
`SQUARE_LOCATION_ID`, `SQUARE_APPLICATION_ID` in `.env`
(`SQUARE_ENVIRONMENT=sandbox`). Test card: `4111 1111 1111 1111`.

Tests (need the Docker DB up; they use the `mi_booking_test` database):

```bash
php artisan test
```

## How it works

- **Slot engine** (`app/Services/AvailabilityService.php`): weekly
  `availability_rules` − `time_blocks` − slot-blocking bookings, on a 15-min
  grid, 24h lead time, 60-day max advance. All wall-clock maths in
  `Australia/Sydney` (DST-safe); storage in UTC.
- **`bookings.ends_at` includes the post-appointment buffer** — it is the
  slot lock end. Customer-facing end time = `starts_at` + service duration.
- **Hold**: submitting details creates a `pending_payment` booking that
  locks the slot for 10 minutes (`BOOKING_HOLD_MINUTES`). The
  `bookings:expire-stale` command (scheduled every minute) flips lapsed
  holds to `abandoned`, releasing the slot. Late payment attempts are
  rejected before charging.
- **Races**: the DB exclusion constraint is the source of truth; controllers
  catch `bookings_no_overlap` violations (savepoint-wrapped) and tell the
  user the slot was taken. If a payment completes just as the hold lapses
  and the slot is retaken, the payment is recorded and the nurse gets a
  NEEDS-REVIEW email (refund or re-slot manually).
- **Refunds**: MVP is manual — cancel in admin (frees slot + emails the
  customer), refund in the Square Dashboard, then "Mark refunded".

## Production checklist

- Real system cron: `* * * * * php artisan schedule:run` (don't rely on traffic)
- `MAIL_MAILER=resend`, `RESEND_API_KEY`, and verify the sending domain
  (`bookings@macarthurinfusions.com.au`) with Resend's DNS records
- Square **production** credentials + `SQUARE_ENVIRONMENT=production`
- Set real prices/durations in `/admin/services` (seeded values are placeholders)
- Confirm weekly hours in `/admin/availability`
- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS only
- Change the admin password
