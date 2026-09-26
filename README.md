# EMS Waitlist & Claim

A Laravel event-management application focused on **capacity-safe booking, FIFO waitlisting, time-limited seat claims, and reliable backend workflows**.

The project was built as a university software-development project and focuses primarily on backend correctness: transactions, concurrency-sensitive booking logic, role-based access, scheduled processing, and regression validation across critical workflows.

## What the application does

The application supports two main user roles:

- **Attendees** can browse events, book available seats, join or leave a waitlist, cancel bookings, and claim seats offered through signed links.
- **Organisers** can create and manage events, access an organiser dashboard, and inspect event waitlists.

When an event is full, attendees can join a FIFO waitlist. If a booked attendee cancels, the next eligible person receives a **15-minute signed claim link**. If the offer expires, a scheduled command moves the expired claimant to the back of the queue and offers the seat to the next eligible attendee.

## Key features

- Event creation, editing, deletion, and public event browsing
- Attendee and organiser authorisation flows
- Capacity-aware event booking
- FIFO event waitlists
- 15-minute temporary signed claim links
- Automatic waitlist progression through a scheduled Artisan command
- Email notification when a waitlisted attendee receives a seat offer
- Database transactions around concurrency-sensitive booking flows
- `lockForUpdate()` row locking around event capacity and waitlist state
- Protection against duplicate/oversold bookings at the application workflow level
- Soft-deleted waitlist entries
- PHPUnit regression coverage for booking, waitlist, authentication, profile, and organiser workflows
- Git-based Laravel project structure suitable for further extension

## Concurrency and booking integrity

One of the main engineering problems in this project is preventing two requests from independently seeing the same available seat and both creating a booking.

The booking flow wraps the critical section in a database transaction and locks the relevant event row before checking capacity:

```text
Booking request
    |
    v
Begin transaction
    |
    v
Lock event row (lockForUpdate)
    |
    v
Count bookings while lock is held
    |
    +-- full --> reject booking
    |
    v
Check active waitlist hold
    |
    +-- held for another attendee --> reject booking
    |
    v
Create booking
    |
    v
Commit transaction
```

The same idea is used when cancelling a booking and when processing expired waitlist offers so that capacity checks and FIFO decisions are made consistently.

> **Note:** the repository still defaults to SQLite for simple local setup. For realistic row-locking and concurrency behaviour, PostgreSQL or MySQL should be used because they provide the locking semantics required by `SELECT ... FOR UPDATE`.

## Waitlist workflow

```text
Event reaches capacity
        |
        v
Attendee joins waitlist
        |
        v
Booking is cancelled
        |
        v
Next FIFO attendee is notified
        |
        v
15-minute signed claim window
       / \
      /   \
 claimed  expired
   |         |
   v         v
booking   scheduled command
created   resets the offer
              |
              v
        next attendee notified
```

The scheduled command is registered to run every minute:

```php
Schedule::command('waitlist:process-expired')->everyMinute();
```

## Automated regression checks

The project uses **PHPUnit 12** through Laravel's testing framework.

The current suite contains **49 passing tests with 125 assertions** and covers behaviours including:

- joining and leaving an event waitlist
- showing waitlist actions when an event is full
- notifying the next waitlisted attendee after a cancellation
- claiming a seat with a temporary signed URL
- rejecting and rotating expired claim windows
- processing expired offers through the Artisan command
- blocking normal bookings while a seat is temporarily held for a waitlisted attendee
- attendee, organiser, profile, registration, and authentication flows

Run the suite with:

```bash
php artisan test
```

The suite is used primarily as a regression safety net while the backend is upgraded and extended. It validates surrounding business rules and transaction-based workflow, but it does **not** yet perform a true multi-process test with simultaneous booking requests.

## Technology stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13 |
| UI | Blade, Tailwind CSS, Alpine.js |
| Frontend tooling | Vite |
| Database | SQLite by default; PostgreSQL targeted for realistic locking/concurrency validation |
| Authentication | Laravel authentication scaffolding / authenticated routes |
| Testing | PHPUnit 12 |
| Scheduling | Laravel Scheduler + Artisan command |
| Email | Laravel Mail |
| Version control | Git / GitHub |

## Local setup

### 1. Clone the repository

```bash
git clone https://github.com/hansenabong/ems-waitlist-claim.git
cd ems-waitlist-claim
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

The default environment uses SQLite. Make sure the database file exists if it has not been created automatically:

```bash
touch database/database.sqlite
```

Then run the migrations and demo seeders:

```bash
php artisan migrate --seed
```

For PostgreSQL, configure the standard Laravel database environment variables (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`) before running migrations.

### 4. Start the application

A convenient development command is:

```bash
composer run dev
```

Alternatively, run the Laravel server and Vite separately:

```bash
php artisan serve
npm run dev
```

> On native Windows/XAMPP environments, Laravel Pail may not run because `pcntl` is unavailable. In that case, run the server, Vite, queue worker, and scheduler as separate processes.

### 5. Run the scheduler

The waitlist expiry workflow depends on Laravel's scheduler:

```bash
php artisan schedule:work
```

## Main application areas

```text
app/
├── Console/Commands/
│   └── ProcessExpiredWaitlist.php
├── Http/Controllers/
│   ├── BookingController.php
│   ├── EventController.php
│   └── EventWaitlistController.php
├── Mail/
└── Models/

tests/
├── Feature/
└── Unit/

routes/
├── web.php
└── console.php
```

## Engineering decisions

### Why use row locking?

Capacity is shared mutable state. Checking the number of bookings and inserting a new booking as two unrelated operations creates a race window. Locking the event row inside a transaction serialises the critical decision for databases that support row-level locking.

### Why use signed claim links?

A waitlist offer should only be usable by the intended authenticated flow and only for a limited period. Laravel temporary signed routes provide tamper detection and expiration for the claim URL.

### Why process expiry with a scheduled command?

Waitlist progression should not depend on a user opening a page or performing another web request. The scheduled command allows expired offers to be processed independently and keeps that background workflow separate from request handling.

### Why keep the UI server-rendered?

The project prioritises backend behaviour and business-rule correctness. Blade keeps the UI layer straightforward while the more interesting engineering work remains visible in the booking, waitlist, transaction, and scheduling logic.

## Current limitations and possible improvements

This is a working learning/portfolio project rather than a finished commercial event platform. Useful next steps include:

- complete the PostgreSQL migration and validate all existing migrations against PostgreSQL
- add a true parallel/concurrency integration test against PostgreSQL
- add CI to run the regression suite automatically on pull requests
- improve the visual design and responsive UX
- add richer event search, filtering, pagination, and organiser reporting
- move mail delivery to queued jobs for production-style operation
- expose selected functionality through a documented REST API
- add observability such as structured logging and application metrics
- add deployment configuration for a public demo environment

## What I learned

This project strengthened my understanding of:

- translating business rules into backend workflows
- database transactions and concurrency-sensitive logic
- FIFO queue behaviour implemented with relational data
- authentication and authorisation boundaries
- signed URLs and time-limited workflows
- scheduled background processing
- using automated regression checks while changing backend behaviour and framework versions
- upgrading Laravel dependencies and resolving PHP/runtime compatibility issues
- debugging interactions between controllers, database state, mail, and scheduled commands

---

Built by **Hansen** as part of my software-development portfolio.
