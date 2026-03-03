SiteGrid — MVP Scaffold (landing page → dashboards → database)

Nice — below is a single, compact, developer-friendly MVP scaffold that covers everything from the landing page to dashboards and the database. It’s organized so you can hand it to a teammate and start building: goals, prioritized features, UX flows (including USSD), APIs, DB schema, jobs, and acceptance criteria.

1 — MVP goal & success criteria

Goal: Deliver a minimal, reliable product that lets site owners register sites, add workers, capture attendance, compute weekly pay, and pay workers (via M-Pesa B2C) while supporting workers on feature phones via USSD. Platform monetizes by charging per-worker/week.

Success criteria (MVP):

A working landing page with owner signup and site creation.

Site owner can add workers and set weekly/daily rates.

Foreman can record attendance (web + simple phone option).

Worker can view balance and claim pay via web/PWA or USSD.

Platform can pay workers via M-Pesa sandbox B2C and log transactions.

Weekly billing of sites (KES 50/worker) computed and invoiced.

2 — Actors & roles

Platform Admin — overall platform settings, billing, dispute resolution.

Site Owner / Manager — creates site(s), funds site (top-up), configures payout preference.

Foreman / Supervisor — approves attendance, validates claims.

Worker (Fundi / Labourer) — claims pay; uses smartphone dashboard or USSD for feature phones.

Payments Provider — M-Pesa (Safaricom Daraja) for collections and B2C payouts.

USSD Provider — Africa’s Talking / Telco provider for USSD sessions.

3 — MVP user stories (prioritized)

As a site owner I can sign up and create a site.

As a site owner I can add workers (name + phone + role + rate).

As a foreman I can record attendance for workers (check-in/out or mark present).

As a worker I can query my balance and claim my weekly pay (web or USSD).

As platform I can compute pay cycles and generate payout batches.

As platform I can execute B2C payouts (sandbox) and log transaction_refs.

As platform I can bill the site owner (KES 50/worker/week) and mark invoices paid/unpaid.

As admin I can view sites, payouts, disputes, and platform revenue.

4 — Landing page (wireframe + content blocks)

Hero:

Headline: "SiteGrid — Simple payroll & attendance for construction sites"

Sub: “Weekly payouts, USSD for feature phones, secure M-Pesa disbursements”

Primary CTA: “Create a site” (signup)

Secondary CTA: “Request a demo” (contact form)

Sections:

How it works (3 steps): Create site → Add workers & capture attendance → Pay workers

Pricing: KES 50 per worker/week (simple example)

Features: USSD, M-Pesa payouts, attendance, dispute logs

Trust: pilot sites, contact, FAQ, contact/support
Footer: links to docs, support, privacy, terms

On the landing page CTA → Owner signup (phone + verification via OTP).

5 — Onboarding flows (quick)

Site Owner

Signup with phone + OTP → create profile → create first site (name, location) → add payment preference (platform or owner’s M-Pesa).

Option: top-up platform wallet via Lipa-na-M-Pesa (C2B) to cover billing & payouts.

Foreman

Owner invites foreman (phone/email). Foreman receives link or USSD code to authenticate.

Worker

Owner adds worker (name, phone, role, rate). If worker wants, worker can claim access via SMS link to web dashboard or simply via USSD using phone number mapping.

6 — Core features & screens (MVP)

Landing page / marketing

Signup form (phone OTP)

Pricing & FAQ

Owner dashboard (web)

Sites list (create/edit)

Site summary: active workers, balance, pending payouts, last pay-cycle

Add worker modal (name, phone, role, rate)

Attendance quick view / approve claims

Billing: invoices, top-up, billing history

Payout settings: platform-managed or owner-managed (owner adds M-Pesa shortcode/token)

Foreman dashboard (mobile-responsive web)

Site selection → Today’s roster

Check-in/out or Mark present (per worker)

Approve worker claims (if required)

Worker access

Smartphone: responsive PWA showing balance, attendance history, claim pay button.

Feature phone: USSD menu (balance, claim pay, last payout).

Admin dashboard

Sites, users, payouts, disputes, revenue reports, webhook logs.

7 — USSD flows (MVP)

Use phone number mapping to worker record.

USSD root: *XXX*YYY# → Simple menu:

Main menu:

1. My balance
2. Claim pay
3. Attendance summary
4. Help

Claim pay flow:

Show last pay cycle amount → Confirm (1: Confirm, 2: Cancel)

If confirmed → create payout request (status: pending approval) → Notify foreman

If owner auto-approve configured → push payout to queue for B2C

Attendance summary:

Show days worked this week, last payout date

Notes:

Keep sessions short; use SMS to send long confirmations/receipts.

8 — Payments & escrow (MVP behavior)

Funding & billing

Site owners top-up platform wallet using Lipa-na-M-Pesa (C2B) OR choose site to use owner’s payout account on demand.

Weekly billing job computes KES 50 * active_workers and either deducts from wallet or sends invoice to owner.

Payroll & payouts

Platform computes wages per worker from the pay cycle (attendance × rate).

If escrow enabled: require owner mark site is_completed = true or require owner approval for payout.

Payout execution:

Queue B2C payout jobs via Daraja sandbox → update payout status with transaction_ref.

On success send SMS/USSD notification to worker.

On failure mark for manual review and notify admin.

Reconciliation

Log all webhook payloads from Daraja into webhook_logs.

Daily reconcile job to match payouts with logs and update statuses.

9 — Database schema (core tables)

Below are the essential tables and key columns. Keep columns minimal for MVP; add indexes on phone, site_id, pay_cycle_id.

-- users
users (
  id BIGINT PK,
  name VARCHAR(255),
  phone VARCHAR(20) UNIQUE,
  email VARCHAR(255) NULL,
  password VARCHAR(255) NULL,
  role ENUM('platform_admin','site_owner','foreman','worker'),
  kyc_status ENUM('pending','verified','rejected') DEFAULT 'pending',
  created_at, updated_at
);

-- sites
sites (
  id BIGINT PK,
  owner_id BIGINT FK -> users(id),
  name VARCHAR(255),
  location VARCHAR(255),
  is_completed TINYINT(1) DEFAULT 0,
  billing_plan JSON NULL,
  created_at, updated_at
);

-- site_workers (pivot)
site_workers (
  id BIGINT PK,
  site_id BIGINT FK -> sites(id),
  user_id BIGINT FK -> users(id),
  role VARCHAR(50),
  daily_rate DECIMAL(10,2) DEFAULT 0,
  weekly_rate DECIMAL(10,2) DEFAULT 0,
  started_at DATE, ended_at DATE NULL,
  created_at, updated_at
);

-- attendance
attendance (
  id BIGINT PK,
  site_id BIGINT FK,
  worker_id BIGINT FK -> users(id),
  date DATE,
  check_in TIME NULL,
  check_out TIME NULL,
  hours INT NULL,
  source ENUM('foreman_web','ussd','qr') DEFAULT 'foreman_web',
  created_at, updated_at
);

-- pay_cycles
pay_cycles (
  id BIGINT PK,
  site_id BIGINT FK,
  start_date DATE,
  end_date DATE,
  status ENUM('open','computed','paid','disputed'),
  created_at, updated_at
);

-- payouts
payouts (
  id BIGINT PK,
  pay_cycle_id BIGINT FK -> pay_cycles(id),
  worker_id BIGINT FK -> users(id),
  gross_amount DECIMAL(10,2),
  fees DECIMAL(10,2) DEFAULT 0, -- platform fee or mpesa fee
  net_amount DECIMAL(10,2),
  status ENUM('pending','queued','processing','paid','failed'),
  paid_at DATETIME NULL,
  transaction_ref VARCHAR(255) NULL,
  created_at, updated_at
);

-- invoices (billing)
invoices (
  id BIGINT PK,
  site_id BIGINT FK,
  period_start DATE,
  period_end DATE,
  amount DECIMAL(10,2),
  status ENUM('unpaid','paid','overdue'),
  created_at, updated_at
);

-- webhook_logs
webhook_logs (
  id BIGINT PK,
  provider VARCHAR(50),
  payload JSON,
  processed BOOLEAN DEFAULT 0,
  created_at
);
10 — Key API endpoints (MVP)

Use versioned API api/v1. Examples:

POST /api/v1/auth/otp — request OTP

POST /api/v1/auth/verify — verify OTP & sign-in

POST /api/v1/sites — create site (owner)

GET /api/v1/sites/{id} — site details

POST /api/v1/sites/{id}/workers — add worker

GET /api/v1/sites/{id}/attendance/today — roster

POST /api/v1/sites/{id}/attendance — mark attendance (foreman)

POST /api/v1/paycycles/{id}/compute — compute pay cycle

POST /api/v1/paycycles/{id}/payouts — trigger payouts batch

POST /api/v1/webhooks/mpesa — mpesa webhooks

POST /api/v1/webhooks/ussd — ussd provider callbacks

All protected endpoints use Sanctum tokens for SPAs/mobile; webhooks validate provider signature.

11 — Background jobs & scheduled tasks

Queue workers: payout processing, MPESA calls, notification sending.

Scheduled cron jobs:

daily: reconcile webhooks & payouts; process failed payouts attempts.

weekly (or configured): compute pay cycles for each site and generate invoice/billing.

hourly: process pending USSD session retries (if needed).

Use Redis + Horizon for queues and visibility.

12 — Security, compliance & data rules

Authentication: phone + OTP (no passwords for workers initially), Sanctum for web tokens.

KYC: require KYC for site owners before high-volume payouts.

Sensitive data: encrypt PII at rest (e.g., national ID if stored). Use HTTPS everywhere.

Webhooks: validate signatures and log raw payloads.

Rate limits: throttle USSD & endpoint hits to prevent abuse.

Audit logs: record who approved/edited attendance/payments for disputes.

13 — Metrics & monitoring (MVP)

Track:

Sites onboarded / active sites

Active workers

Weekly payouts count & volume

Platform revenue (fees collected)

USSD success rate / drop-off

Failed payouts ratio
Use logging (daily reconciliation) + simple dashboard for admin.

14 — Acceptance criteria (for each epic)

Signup & site creation

Owner can create account with phone + OTP and create a site. Test: create account → create site → site appears in owner dashboard.

Add workers

Owner can add worker with phone. Worker should be findable by phone. Test: add 3 workers → list shows 3.

Attendance

Foreman can mark present for worker for a date. Test: mark 5 workers present → attendance rows stored.

Compute pay

System computes gross & net based on attendance and rates. Test: create paycycle for period → computed payouts entries created with amounts.

Payout

System queues B2C payouts and records transaction refs from MPESA webhook. Test: run payout in sandbox → webhook updates payout to paid.

USSD

Worker can dial USSD to see balance and create a payout request. Test: simulate USSD provider callback → platform creates payout request row.

Billing

Weekly invoice generated with KES 50 * active_workers. Test: invoice shows correct amount.

15 — Minimal tech stack & packages (MVP)

Backend: Laravel 10+

DB: MySQL / MariaDB

Queues: Redis

Auth: Laravel Sanctum

Packages: spatie/permission, propaganistas/laravel-phone, itsmurumba/laravel-mpesa (or a maintained equivalent)

USSD: Africa’s Talking (or provider) webhook integration

Frontend: Tailwind + Alpine/Vue (PWA optional)

Hosting: DigitalOcean / AWS Lightsail; later scale to AWS ECS / RDS

16 — First-PR checklist (developer ready)

Initialize repo + README + .env.example

Add migrations for users, sites, site_workers, attendance, pay_cycles, payouts, invoices, webhook_logs

Seed demo data (1 owner, 1 site, 5 workers)

Implement OTP-based auth (phone + SMS)

Implement site creation & worker add endpoints

Implement attendance endpoint (foreman)

Implement pay-cycle compute (local computation only) and show results in owner dashboard

Integrate MPESA sandbox token retrieval & webhook logging

17 — Pilot & rollout plan (short)

Choose 1–2 pilot sites (small): test enrollment, attendance flows, USSD flow, and payouts in sandbox.

Run 4 weekly cycles to validate attendance → compute → payout → reconcile.

Collect feedback from owner, foreman, workers for UX improvements.

After successful pilot, apply to the MPESA live environment and enable real payouts.

18 — Risks & mitigations (MVP)

Attendance fraud — mitigation: foreman approvals, photo/GPS audit for later phases.

Cashflow for payouts — mitigation: require site owners to pre-fund wallet or auto-hold credited payroll.

Telco costs — include USSD/session costs in pricing or bill owner.

Dispute handling — keep detailed logs and simple dispute resolution workflow.