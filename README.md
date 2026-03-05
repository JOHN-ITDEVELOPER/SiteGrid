# Mjengo - Worker Management & Payment Platform

A comprehensive Laravel-based platform for managing casual workers, site operations, attendance tracking, and financial flows via M-Pesa integration.

## Overview

Mjengo is a complete solution for:
- **Worker Management** - Recruitment, onboarding, KYC verification, and suspension
- **Attendance Tracking** - Daily punch-ins, corrections, and evidence collection
- **Payroll Processing** - Automated invoice generation, payout approvals, M-Pesa disbursement
- **Financial Control** - Platform revenue tracking, fee analysis, reconciliation
- **Inventory Management** - Site inventory requests, procurement tracking, progress monitoring
- **Multi-Channel Access** - Web (Admin/Owner/Foreman/Worker), USSD, API

## Tech Stack

- **Backend**: Laravel 10+ (PHP 8.1+)
- **Database**: MySQL 8.0+
- **Payment Gateway**: Safaricom M-Pesa Daraja API (STK Push, B2C)
- **Frontend**: Bootstrap 5, jQuery, Blade templates
- **Authentication**: Session-based with role-based access control (RBAC)
- **Queue System**: Laravel Queue (configurable: database, redis, sync)

## Key Features

### 1. Payment Routing & Revenue Tracking
- **Multi-account M-Pesa configuration** - Separate accounts for deposits, invoices, payouts
- **Platform Revenue Dashboard** - Track all invoice payments received from owners
- **Account Activation & Testing** - OAuth sandbox testing before going live
- **Audit Trails** - Complete history of all payment routing decisions

### 2. Financial Reports
- **Revenue Report** - Payout costs by site and pay cycle
- **Platform Revenue Report** - Invoice payments received with reconciliation status
- **Fee Analysis** - Platform fees vs M-Pesa fees breakdown
- **Reconciliation** - Payout status tracking and dispute management
- **CSV Export** - Download reports for external analysis

### 3. Worker Management
- **Roster Management** - Add/remove workers from sites
- **KYC Verification** - Document upload and admin approval
- **Attendance Tracking** - Daily punch-ins with GPS/photo evidence
- **Worker Suspension** - Account suspension with automated payout holds
- **Worker History** - Complete activity audit log

### 4. Invoice & Payout System
- **Auto Invoice Generation** - Weekly/bi-weekly invoices for owner billing
- **Invoice Payment** - STK Push integration for owner payments
- **Payout Approvals** - Admin approval workflow before B2C disbursement
- **Payout Adjustments** - Corrections, deductions, and supplemental payments
- **Payment Status Tracking** - Real-time payout status updates

### 5. Site & Owner Management
- **Site Registration** - Owner onboarding with site details
- **Site Policies** - Worker count limits, attendance requirements, quality metrics
- **Wallet Management** - Owner wallet top-ups and balance tracking
- **Site Settings** - Customizable payment schedules and invoice due dates

### 6. Inventory Management
- **Procurement Requests** - Worker/site inventory requests
- **Progress Tracking** - Daily photo evidence collection
- **Admin Approvals** - Bulk approval workflows
- **Inventory History** - Complete request audit trail

## Installation

### Prerequisites
```bash
- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js 16+ (for frontend assets)
```

### Setup

1. **Clone repository**
```bash
git clone <repository-url>
cd mjengo
```

2. **Install dependencies**
```bash
composer install
npm install
```

3. **Environment configuration**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Database setup**
```bash
php artisan migrate
php artisan db:seed  # Optional: seed with test data
```

5. **Build assets**
```bash
npm run build  # Production
npm run dev    # Development
```

6. **Start application**
```bash
php artisan serve
# Or use XAMPP/LAMP stack
```

## Configuration

### M-Pesa Integration

1. **Register with Safaricom** - Get Consumer Key/Secret from Daraja portal
2. **Admin → Settings → Payment Accounts**
   - Create Escrow Account (for deposits)
   - Create Invoice Revenue Account (for invoice payments)
   - Create Payout Account (for worker disbursement)
3. **Test Connection** - OAuth sandbox test before going live
4. **Set as Primary** - Mark accounts as primary for each type

### Email Configuration
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### Queue Configuration
```env
QUEUE_CONNECTION=database  # or redis, sync
```

## Usage

### Admin Dashboard
- **Financial Reports** - `/admin/financial/dashboard`
- **Payment Accounts** - `/admin/settings?tab=accounts`
- **Payouts** - `/admin/payouts`
- **Invoices** - `/admin/invoices`
- **Users** - `/admin/users`
- **Sites** - `/admin/sites`
- **Audit Log** - `/admin/audit`

### Owner Portal
- **Dashboard** - Overview of sites and finances
- **Invoices** - View and pay invoices via M-Pesa
- **Sites** - Manage worker rosters
- **Attendance** - Daily worker attendance verification
- **Workers** - Worker management and KYC

### Worker App
- **Check-in** - Daily punch-in with GPS/photo
- **Dashboard** - Earnings and payout status
- **History** - Payment history and receipts

### Foreman Mobile
- **Attendance** - Verify worker punch-ins
- **Progress** - Log inventory request progress

## API Endpoints

### M-Pesa Callbacks
```
POST /api/mpesa/callback - Lipa Na M-Pesa STK/B2C responses
POST /api/ussd/callback - USSD session handling
```

### Worker API
```
GET  /api/v1/worker - Worker profile
POST /api/v1/worker/checkin - Daily attendance
GET  /api/v1/earnings - Earnings summary
```

## Backfill utility

For historical data migration:
```bash
# Backfill platform_revenue for paid invoices (one-time)
php artisan backfill:platform-revenue --simulate  # Preview
php artisan backfill:platform-revenue             # Execute
```

## Database Schema

### Core Tables
- `users` - All user accounts (admin, owner, worker, foreman)
- `sites` - Owner work sites
- `invoices` - Owner billing
- `payouts` - Worker payments
- `mpesa_transactions` - M-Pesa callback tracking
- `attendance` - Daily worker punch-ins
- `inventory_requests` - Procurement requests
- `inventory_evidence` - Progress photos

### Financial Tables
- `platform_accounts` - M-Pesa account configuration
- `platform_revenue` - Invoice payment audit trail
- `platform_settings` - System-wide configuration
- `payout_adjustments` - Corrections & deductions

## Security

- **Password Reset** - Email & SMS OTP flows
- **Account Suspension** - Automatic payout blocks
- **Audit Logging** - Complete action history
- **CSRF Protection** - Token-based form submission
- **Rate Limiting** - API and login throttling
- **Data Encryption** - Sensitive credentials stored encrypted

## Troubleshooting

### M-Pesa Test Failing
- Verify Consumer Key/Secret for Sandbox environment
- Check IP whitelist in Daraja portal
- Test with fake details first for OAuth validation

### Platform Revenue Empty
- Mark Invoice Account as "Primary" in Payment Accounts
- Ensure invoice was paid via M-Pesa (not manually marked)
- Check `platform_revenue` table directly

### Missing Migrations
```bash
php artisan migrate:reset
php artisan migrate
```

## File Structure

```
mjengo/
├── app/
│   ├── Console/Commands/        # Artisan commands
│   ├── Http/Controllers/        # Route controllers
│   ├── Models/                  # Eloquent models
│   ├── Services/                # Business logic
│   └── Jobs/                    # Queue jobs
├── database/
│   ├── migrations/              # Schema changes
│   └── seeders/                 # Test data
├── resources/
│   ├── views/                   # Blade templates
│   ├── css/                     # Tailwind/CSS
│   └── js/                      # JavaScript
├── routes/                      # URL definitions
└── config/                      # Configuration files
```

## Contributing

1. Create feature branch (`git checkout -b feature/amazing-feature`)
2. Commit changes (`git commit -m 'feat: add amazing feature'`)
3. Push to branch (`git push origin feature/amazing-feature`)
4. Open Pull Request

## Documentation

- [Financial Control Quick Start](./FINANCIAL_CONTROL_QUICK_START.md)
- [M-Pesa Setup Guide](./MPESA_SETUP_GUIDE.md)
- [USSD Setup Guide](./USSD_SETUP_GUIDE.md)
- [Navigation Map](./NAVIGATION_MAP.md)

## Support

For issues and feature requests, please use the issue tracker.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Changelog

### v1.0.0 (Mar 6, 2026)
- Initial release with full payment routing & financial control system
- Multi-account M-Pesa configuration
- Platform Revenue tracking dashboard
- Financial reports suite
- Payout approval workflow
- Worker management system
