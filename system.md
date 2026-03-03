# Mjengo — Laravel Project Scaffold

This document contains a recommended repository layout, commands to scaffold the project, useful packages, starter `.env` notes, sample migrations, and short starter code snippets (USSD, MPESA integration skeletons, and basic controllers). Use it as the single source of truth when you create the repository.

---

## Goals

* Fast, maintainable Laravel codebase for the Mjengo platform.
* Clear separation of concerns (modules for Payments, USSD, Attendance, Payroll, Billing).
* Ready for team collaboration and CI/CD.

---

## Quick commands (create project + git init)

```bash
# create laravel project (using laravel installer or composer)
composer create-project laravel/laravel mjengo
cd mjengo

# init git
git init
git add .
git commit -m "chore: initial Laravel project"

# create dev branches
git branch -M main

# install recommended packages (examples)
composer require laravel/sanctum
composer require spatie/laravel-permission
composer require laravel/telescope --dev
composer require guzzlehttp/guzzle
composer require propaganistas/laravel-phone

# optionally: mpesa package (or create your own service)
composer require itsmurumba/laravel-mpesa

# node (frontend)
npm install
npm run dev
```

---

## Recommended `.gitignore` additions

```
/vendor
/node_modules
.env
/public/storage
/storage/*.key
Homestead.json
Homestead.yaml
```

---

## Top-level repo structure (recommended)

```
sitegrid/
├─ app/
│  ├─ Console/
│  ├─ Exceptions/
│  ├─ Http/
│  │  ├─ Controllers/
│  │  │  ├─ Auth/
│  │  │  ├─ Api/
│  │  │  │  ├─ V1/
│  │  │  │  │  ├─ SiteController.php
│  │  │  │  │  ├─ WorkerController.php
│  │  │  │  │  └─ PaymentController.php
│  │  │  ├─ Web/
│  │  │  │  └─ DashboardController.php
│  │  ├─ Middleware/
│  ├─ Models/
│  │  ├─ User.php
│  │  ├─ Site.php
│  │  ├─ Worker.php
│  │  ├─ Attendance.php
│  │  └─ PayCycle.php
│  ├─ Services/
│  │  ├─ MpesaService.php
│  │  ├─ UssdService.php
│  │  └─ PayrollService.php
│  └─ Providers/
├─ bootstrap/
├─ config/
│  ├─ mpesa.php
│  └─ ussd.php
├─ database/
│  ├─ migrations/
│  ├─ seeders/
│  └─ factories/
├─ resources/
│  ├─ views/
│  └─ js/
├─ routes/
│  ├─ web.php
│  └─ api.php
├─ tests/
├─ storage/
└─ .env
```

Notes:

* `app/Services` holds integrations and business logic (MPESA, USSD, Payroll). Keep controllers thin.
* Use API versioning under `Http/Controllers/Api/V1` so future breaking changes are easier to manage.

---

## Suggested modules (high level)

* **Auth**: Owner/site sign-up, 2FA via SMS optional
* **Sites**: create/manage site, mark completed
* **Workers**: add workers, roles (foreman, fundi, labourer)
* **Attendance**: checkin/checkout, USSD checkin, QR checkin
* **Payroll**: pay cycles, compute net pay, deductions
* **Payments**: MPESA integration, B2C payouts, tracking transaction references
* **Billing**: platform charge per-worker/week
* **Disputes / Audit**: mark disputes and store evidence (photos, logs)

---

## Environment variables (core)

```
APP_NAME=Mjengo
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mjengo
DB_USERNAME=root
DB_PASSWORD=

MPESA_SHORTCODE=174379
MPESA_CONSUMER_KEY=your_key
MPESA_CONSUMER_SECRET=your_secret
MPESA_PASSKEY=your_passkey
MPESA_ENV=sandbox

USSD_PROVIDER_KEY=...
USSD_PROVIDER_SECRET=...
USSD_CALLBACK_URL=https://yourapp.com/webhooks/ussd

SANCTUM_STATEFUL_DOMAINS=localhost
```

---

## Example migration snippets

**users table** (minimal):

```php
Schema::create('users', function (Blueprint $table) {
  $table->id();
  $table->string('name');
  $table->string('phone')->unique();
  $table->string('email')->nullable()->unique();
  $table->string('password');
  $table->enum('role', ['platform_admin','site_owner','foreman','worker'])->default('worker');
  $table->timestamps();
});
```

**sites table**:

```php
Schema::create('sites', function (Blueprint $table) {
  $table->id();
  $table->foreignId('owner_id')->constrained('users');
  $table->string('name');
  $table->string('location')->nullable();
  $table->boolean('is_completed')->default(false);
  $table->timestamps();
});
```

**site_workers** (pivot / details):

```php
Schema::create('site_workers', function (Blueprint $table) {
  $table->id();
  $table->foreignId('site_id')->constrained('sites');
  $table->foreignId('user_id')->constrained('users');
  $table->string('role');
  $table->decimal('daily_rate', 8, 2)->default(0);
  $table->timestamps();
});
```

**attendance** (simple):

```php
Schema::create('attendance', function (Blueprint $table) {
  $table->id();
  $table->foreignId('site_id')->constrained('sites');
  $table->foreignId('worker_id')->constrained('users');
  $table->date('date');
  $table->time('check_in')->nullable();
  $table->time('check_out')->nullable();
  $table->integer('hours')->nullable();
  $table->timestamps();
});
```

---

## Example Service skeletons (where to implement logic)

```
app/Services/MpesaService.php       // handle token, B2C, C2B callbacks
app/Services/UssdService.php        // build USSD menus, parse input, call controllers
app/Services/PayrollService.php     // compute pay per cycle, apply fees
```

Keep these classes small and well tested.

---

## API routes recommendations (api.php)

```
Route::prefix('v1')->group(function () {
  Route::post('auth/login', ...);
  Route::post('sites', SiteController@store)->middleware('auth:sanctum');
  Route::get('sites/{site}', SiteController@show);

  Route::post('sites/{site}/workers', WorkerController@store);
  Route::post('paycycles/{paycycle}/payout', PaymentController@payout);

  // webhooks
  Route::post('webhooks/mpesa', MpesaWebhookController@handle);
  Route::post('webhooks/ussd', UssdWebhookController@handle');
});
```

---

## USSD & MPESA integration notes

* Implement webhooks that validate incoming signatures where possible.
* Use a queue for heavy operations (e.g., batching payouts) — Laravel queues + Redis recommended.
* Log every webhook payload to a dedicated `webhook_logs` table for debugging and reconciliation.

---

## Billing automation

* Create a weekly cron job (`php artisan schedule:run`) to: compute charges per active worker, generate invoices, and charge site owner balance or send an M-Pesa payment request.

---

## Example CI / GitHub Actions (basic)

```yaml
name: CI
on: [push]
jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2
      - run: composer install -q --no-scripts
      - run: cp .env.example .env
      - run: php artisan key:generate
      - run: php artisan test -- --colors=never
```

---

## Starter checklist (first PR)

* [ ] Create repository and push initial Laravel skeleton.
* [ ] Add README with project purpose and development steps.
* [ ] Add .env.example, .gitignore, CODE_OF_CONDUCT, CONTRIBUTING.md.
* [ ] Create migrations for core tables (users, sites, site_workers, attendance, pay_cycles, payouts).
* [ ] Seeders for sample demo data (1 site owner, 1 site, 5 workers).
* [ ] Implement basic Site creation + Worker add endpoints.
* [ ] Implement MPESA sandbox token retrieval and webhook logging.

---

## Next steps I can do for you right now

1. Generate the `composer` + `git` commands as a shell script to run locally.
2. Create the actual Laravel migration files and seeders (I can produce PHP code you can paste into your project).
3. Scaffold the Laravel controllers and service classes (skeleton code).

Tell me which of the three you want next and I'll create it for you.
