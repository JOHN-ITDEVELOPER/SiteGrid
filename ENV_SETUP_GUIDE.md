# SiteGrid Landing Page — Environment Setup Guide

This guide explains all environment variables needed for the landing page and related integrations.

---

## Quick Start

### 1. Copy environment template
```bash
cp .env.example .env
```

### 2. Generate application key
```bash
php artisan key:generate
```

### 3. Run migrations
```bash
php artisan migrate
```

### 4. Start development server
```bash
php artisan serve
npm run dev
```

Visit: `http://localhost:8000`

---

## Core Settings

### APP Configuration
```env
APP_NAME=SiteGrid
APP_ENV=local          # local, staging, production
APP_DEBUG=true         # false in production
APP_URL=http://localhost:8000
```

### Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sitegrid
DB_USERNAME=root
DB_PASSWORD=
```

---

## Landing Page Features

### Email Configuration (Demo Requests & OTP)

**Option 1: Mailtrap** (Easiest for development)
```env
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=noreply@mjengo.local
MAIL_FROM_NAME=Mjengo
```
- Sign up: https://mailtrap.io
- Free tier: 500 emails/month
- Inbox captures all emails (safe for testing)

**Option 2: SendGrid** (Recommended for production)
```env
MAIL_DRIVER=sendgrid
SENDGRID_API_KEY=your_sendgrid_api_key
MAIL_FROM_ADDRESS=noreply@mjengo.local
MAIL_FROM_NAME=Mjengo
```
- Sign up: https://sendgrid.com
- Free tier: 100 emails/day
- Transactional email pro

**Option 3: Gmail**
```env
MAIL_DRIVER=gmail
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
```
- Less secure app password required
- Good for testing, use SendGrid for production

### Analytics & Tracking

**Google Tag Manager** (Recommended)
```env
GTM_ID=GTM-XXXXXX
```
- Create account: https://tagmanager.google.com
- Create container for your domain
- Copy GTM ID (format: GTM-XXXXXX)
- Link to Google Analytics 4 property

**Sentry Error Tracking** (Optional, for production)
```env
SENTRY_LARAVEL_DSN=https://your_key@your_project.ingest.sentry.io/your_id
SENTRY_TRACES_SAMPLE_RATE=0.1
```
- Sign up: https://sentry.io
- Free tier: 5,000 errors/month
- Captures errors and performance

---

## USSD & SMS (Africa's Talking)

### Setup
1. Sign up: https://africastalking.com
2. Verify phone number (your personal phone)
3. Go to "My Apps" → Create new app
4. Copy API Key from account settings
5. Add credentials:

```env
AFRICASTALKING_USERNAME=your_username
AFRICASTALKING_API_KEY=your_api_key
AFRICASTALKING_SHORTCODE=12345
```

### Create Shortcode (USSD)
1. Dashboard → SMS → Shortcodes
2. Create new (e.g., `*123*456#`)
3. Callback URL: `https://yourdomain.com/ussd/callback`
4. Confirm SMS to your phone

### Test OTP Flow
```bash
# OTP will be logged to storage/logs/laravel.log
# In development, check logs to get the code
```

---

## M-Pesa / Safaricom Daraja

### Sandbox Setup (Testing)
1. Sign up: https://developer.safaricom.co.ke
2. Confirm email
3. Create app → Copy credentials

```env
SAFARICOM_ENVIRONMENT=sandbox
SAFARICOM_CONSUMER_KEY=your_consumer_key
SAFARICOM_CONSUMER_SECRET=your_consumer_secret
SAFARICOM_PASSKEY=your_passkey
SAFARICOM_SHORTCODE=123456
SAFARICOM_INITIATOR=testuser
SAFARICOM_INITIATOR_PASSWORD=Safcom496!
```

### Get Sandbox Credentials
1. My Apps → Click your app
2. Copy Consumer Key & Secret
3. Go to Developers tab → Copy Passkey
4. Test numbers available in docs

### Switch to Production
```env
SAFARICOM_ENVIRONMENT=production
```
- Requires live account
- Credentials from production app
- Process payouts to real M-Pesa accounts

---

## Rate Limiting (Security)

Add to `.env` to prevent abuse:
```env
RATE_LIMIT_SIGNUP=3,60        # 3 signup attempts per 60 minutes per IP
RATE_LIMIT_OTP=5,5            # 5 OTP tries per 5 minutes
RATE_LIMIT_DEMO=5,60          # 5 demo requests per hour
```

Update `routes/web.php`:
```php
Route::post('/signup/phone', [LandingController::class, 'submitPhone'])
    ->middleware('throttle:3,1'); // 3 per minute
```

---

## Cache & Session (Performance)

### Development
```env
CACHE_DRIVER=file
SESSION_DRIVER=file
```

### Production (Redis)
```bash
# Install Redis
apt-get install redis-server

# In .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

## Logging & Debugging

### Development
```env
LOG_CHANNEL=stack
LOG_LEVEL=debug
APP_DEBUG=true
```

### Production
```env
LOG_CHANNEL=single
LOG_LEVEL=error
APP_DEBUG=false
```

### View Logs
```bash
# Real-time logs
tail -f storage/logs/laravel.log

# Or in code
Log::info('Signup initiated', ['phone' => $phone]);
```

---

## HTTPS & Security (Production)

```env
APP_URL=https://sitegrid.local
FORCE_HTTPS=true

# Session security
SESSION_SECURE_COOKIES=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# CORS (if API on different domain)
CORS_ALLOWED_ORIGINS=https://sitegrid.local,https://api.sitegrid.local
```

---

## Database Backups

### MySQL
```bash
# Backup
mysqldump -u root -p sitegrid > sitegrid_backup.sql

# Restore
mysql -u root -p sitegrid < sitegrid_backup.sql
```

### Automated (Cron)
```bash
# Add to crontab (backup daily at 2 AM)
0 2 * * * mysqldump -u root -p$DB_PASSWORD $DB_DATABASE > /backups/$(date +\%Y\%m\%d).sql
```

---

## Testing Environment Variables

### Create .env.testing
```bash
cp .env .env.testing
```

```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
QUEUE_CONNECTION=sync
```

### Run Tests
```bash
php artisan test
```

---

## Troubleshooting

### "OTP not received"
- Check AFRICASTALKING_USERNAME & API_KEY
- Verify phone number is valid +254 format
- Check logs: `tail -f storage/logs/laravel.log`

### "Email not sending"
- Verify MAIL credentials in .env
- Check MAIL_FROM_ADDRESS is set
- Test with Mailtrap first (inbox shows all emails)
- Debug: `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('email@example.com'))`

### "M-Pesa B2C fails"
- Ensure SAFARICOM_ENVIRONMENT=sandbox initially
- Verify SAFARICOM_CONSUMER_KEY/SECRET
- Check SAFARICOM_SHORTCODE is valid
- Test with sandbox money first (~KES 100)

### "GTM events not firing"
- Verify GTM_ID in .env matches container
- Check GTM container is published (green checkmark)
- Open DevTools → Network tab → search "gtm.js"
- Verify `window.gtag` is defined in console

### Session expires quickly
- Increase SESSION_LIFETIME in .env (default 120 minutes)
- Or use database sessions: `php artisan session:table`

---

## Production Deployment Checklist

### Environment Variables
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] APP_KEY is set
- [ ] DB_HOST, DB_USERNAME, DB_PASSWORD correct
- [ ] MAIL_DRIVER configured (SendGrid or AWS SES)
- [ ] GTM_ID set
- [ ] AFRICASTALKING credentials (production account)
- [ ] SAFARICOM credentials (production)
- [ ] Secrets stored in production secret manager (not .env)

### Security
- [ ] HTTPS enforced (FORCE_HTTPS=true)
- [ ] SESSION_SECURE_COOKIES=true
- [ ] CSRF protection enabled
- [ ] Rate limiting active
- [ ] Error logging to Sentry or similar
- [ ] Database backups automated

### Performance
- [ ] CACHE_DRIVER=redis
- [ ] SESSION_DRIVER=redis
- [ ] Compiled assets (npm run build)
- [ ] Image compression & CDN
- [ ] Lighthouse scores > 90

### Monitoring
- [ ] Error tracking (Sentry, Bugsnag)
- [ ] Uptime monitoring (UptimeRobot)
- [ ] Database monitoring
- [ ] CPU/memory alerts (Datadog, New Relic)
- [ ] GTM & GA tracking verified

---

## Useful Commands

```bash
# Clear all caches
php artisan cache:clear

# Fresh database
php artisan migrate:fresh --seed

# Generate new APP_KEY
php artisan key:generate

# Tinker shell (test code)
php artisan tinker

# Queue worker (for async jobs)
php artisan queue:work

# Create admin user (manual)
php artisan tinker
> App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password')])

# Watch for file changes & recompile CSS
npm run dev
```

---

## Support

**Issues?**
- Check logs: `storage/logs/laravel.log`
- Review environment variables: `php artisan env`
- Test email: `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@example.com'))`
- Africa's Talking support: support@africastalking.com
- Safaricom Daraja support: daraja@safaricom.co.ke

**Docs:**
- Laravel Env: https://laravel.com/docs/configuration
- Africa's Talking: https://africastalking.com/sms
- Safaricom Daraja: https://developer.safaricom.co.ke
- Google Tag Manager: https://tagmanager.google.com/

---

**Last Updated**: 2026-02-23  
**Maintained By**: [Your Team]
