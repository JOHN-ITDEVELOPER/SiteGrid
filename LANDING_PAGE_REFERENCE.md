# Mjengo Landing Page — Quick Reference Card

A one-page overview of the complete landing page implementation. Use this as a checklist and quick lookup guide.

---

## 📋 What's Implemented

| Component | Status | File | Notes |
|-----------|--------|------|-------|
| Landing page HTML | ✅ | `resources/views/landing.blade.php` | Full Tailwind CSS, all sections |
| Phone signup modal | ✅ | `resources/views/landing.blade.php` | Form + client-side validation |
| OTP verification | ✅ | `resources/views/landing.blade.php` | Modal + resend countdown |
| Demo request form | ✅ | `resources/views/landing.blade.php` | Inline form, email-ready |
| Backend controller | ✅ | `app/Http/Controllers/LandingController.php` | Phone validation, OTP, demo handling |
| Routes | ✅ | `routes/web.php` | All endpoints configured |
| Dashboard (starter) | ✅ | `resources/views/dashboard/index.blade.php` | Post-signup landing page |
| Documentation | ✅ | `LANDING_PAGE_SPEC.md` | Full technical spec |
| Setup guide | ✅ | `LANDING_PAGE_GUIDE.md` | Integration & customization |
| Env config guide | ✅ | `ENV_SETUP_GUIDE.md` | All variables explained |

---

## 🎨 Design System

```
Colors:
  Primary:     #1e1b4b (Deep Indigo)      — Headers, logos, trust
  Accent:      #f97316 (Warm Orange)      — CTAs, highlights
  Text:        #374151 (Gray-700)         — Body text
  Background:  #ffffff (White)            — Clean, minimal

Breakpoints:
  sm: 640px   (small phones)
  md: 768px   (tablets & large phones)
  lg: 1024px  (desktops)
  
Font:
  System fonts (no custom fonts = faster load)
  H1: 3.75rem bold,  H2: 2.25rem bold,  Body: 1rem regular
```

---

## 🎯 Key Pages & Routes

| Route | Purpose | Handler | Status |
|-------|---------|---------|--------|
| `/` | Landing page | `LandingController::index()` | ✅ Live |
| `/signup/phone` (POST) | Phone submission | `LandingController::submitPhone()` | ✅ Ready |
| `/signup/verify-otp` (POST) | OTP verification | `LandingController::verifyOtp()` | ✅ Ready |
| `/signup/resend-otp` (POST) | Resend code | `LandingController::resendOtp()` | ✅ Ready |
| `/demo` (POST) | Demo request | `LandingController::submitDemo()` | ✅ Ready |
| `/dashboard` | Onboarding | Shows sites, quick start | ✅ Starter |
| `/login` | Sign in | Auth form (not yet built) | ⏳ TODO |

---

## 📱 Form Flows

### Signup (Phone → OTP → Dashboard)
```
User clicks "Create a site"
    ↓
Phone input modal appears
    ↓
User enters +254 7XXXXXXXX, name (optional)
    ↓
Client: validate (9-13 digits)
Server: normalize, store OTP in session (5 min expiry)
    ↓
OTP modal appears (masked phone: +254 ••••••••)
    ↓
User enters 6-digit code (with resend option)
    ↓
Server: verify code, create user if new, authenticate
    ↓
Redirect to /dashboard (post-signup onboarding)
```

### Demo Request
```
User fills: name, company, email, phone, message
    ↓
Server: validate
    ↓
Store in future DemoRequest table
Email to: sales@mjengo.local, cc: founder@mjengo.local
    ↓
Show toast: "Demo request sent! We'll contact you soon."
```

---

## 🔧 Integration Checklist (MVP → Prod)

### Immediate (This Week)
- [ ] Landing page deployed to `mjengo.local`
- [ ] Hero image added to `public/images/`
- [ ] Google Analytics & GTM container created
- [ ] Mailtrap email configured (for testing)
- [ ] Test phone signup flow (e2e)

### Sprint 1 (Week 2-3)
- [ ] Africa's Talking USSD OTP integration
- [ ] Switch from session OTP to database OTP
- [ ] Email notifications for demo requests (SendGrid setup)
- [ ] Rate limiting on signup endpoints
- [ ] Create onboarding wizard (site + worker setup)

### Sprint 2+ (Weeks 4+)
- [ ] M-Pesa B2C payouts (sandbox → production)
- [ ] Foreman attendance dashboard
- [ ] Worker USSD check-in callback
- [ ] Payroll calculation engine
- [ ] Admin analytics & reporting

---

## 🚀 Quick Start Commands

```bash
# 1. Install dependencies
composer install
npm install

# 2. Setup environment
cp .env.example .env
php artisan key:generate
php artisan migrate

# 3. Build frontend assets
npm run dev         # Development (watch mode)
npm run build       # Production (minified)

# 4. Start servers
php artisan serve       # Laravel (http://localhost:8000)
# npm run dev also watches & rebuilds

# 5. View logs
tail -f storage/logs/laravel.log

# 6. Test signup flow
# - Visit http://localhost:8000
# - Click "Create a site"
# - Enter phone number
# - Check logs for OTP code
# - Enter OTP in modal
```

---

## 📊 Analytics Events to Track

```javascript
// Implemented events:
trackEvent('signup_initiated', { method: 'cta_click' })
trackEvent('otp_verified', { duration: 120 }) // seconds
trackEvent('site_created', { worker_count: 10 })
trackEvent('demo_requested', { email: '...' })
trackEvent('cta_clicked', { button_text: '...' })

// Via Google Tag Manager
// Dashboard: https://tagmanager.google.com
// Property: https://analytics.google.com
```

---

## 🔐 Security Notes

| Aspect | Status | Notes |
|--------|--------|-------|
| CSRF tokens | ✅ | All forms use `@csrf` |
| Input validation | ✅ | Client + server, phone regex |
| OTP expiry | ✅ | 5 minutes, stored in session |
| Password hashing | ✅ | bcrypt, Laravel auto-hashes |
| HTTPS | ⏳ TODO | Enable in production |
| Rate limiting | ⏳ TODO | Add throttle:3,1 to signup route |
| USSD provider | ⏳ TODO | Africa's Talking integration |

---

## 📈 Performance Targets

```
Core Web Vitals:
  LCP (Largest Contentful Paint):        < 2.5s  ⏳ To measure
  FID (First Input Delay):               < 100ms ⏳ To measure
  CLS (Cumulative Layout Shift):         < 0.1   ⏳ To measure

Lighthouse Scores:
  Performance:  > 90  ⏳ To measure
  Accessibility: > 90 ⏳ To measure
  SEO:           > 90 ⏳ To measure
```

**Optimizations:**
- Tailwind CSS (purged for production)
- No render-blocking JavaScript
- Images: WebP + PNG, lazy-loaded
- System fonts (no custom font files)
- Minified & gzip'd assets

---

## 🗂️ File Structure Reference

```
landing/ (key files)
├── resources/views/
│   ├── landing.blade.php           ← MAIN: HTML + CSS + JS
│   └── dashboard/
│       └── index.blade.php         ← Post-signup dashboard
├── app/Http/Controllers/
│   └── LandingController.php       ← BACKEND: Form handlers
├── routes/
│   └── web.php                     ← ROUTES: Endpoints
├── public/images/                  ← IMAGES: Hero, OG
├── LANDING_PAGE_SPEC.md            ← Full spec & copy
├── LANDING_PAGE_GUIDE.md           ← Setup & customization
├── ENV_SETUP_GUIDE.md              ← Environment vars
└── .env                            ← Config (ADD YOUR VARS)
```

---

## ⚙️ Environment Variables (Minimal Setup)

```env
# .env

# App
APP_KEY=base64:xxxxx          # Run: php artisan key:generate
APP_URL=http://localhost:8000

# Database
DB_DATABASE=mjengo
DB_USERNAME=root
DB_PASSWORD=

# Email (for demo requests)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_USERNAME=your_mailtrap_user
MAIL_PASSWORD=your_mailtrap_password
MAIL_FROM_ADDRESS=noreply@mjengo.local

# GTM (Analytics)
GTM_ID=GTM-XXXXXX

# SMS (Africa's Talking - when ready)
AFRICASTALKING_USERNAME=xxx
AFRICASTALKING_API_KEY=xxx

# M-Pesa (Safaricom - when ready)
SAFARICOM_CONSUMER_KEY=xxx
SAFARICOM_CONSUMER_SECRET=xxx
```

See `ENV_SETUP_GUIDE.md` for full list & explanations.

---

## 🐛 Common Issues & Fixes

| Issue | Cause | Fix |
|-------|-------|-----|
| "Landing page blank" | CSS not compiled | Run `npm run dev` or `npm run build` |
| "CSRF token mismatch" | Missing `@csrf` in form | Add `@csrf` to form, ensure POST |
| "OTP not sending" | Africa's Talking not set up | Add credentials to .env, update controller |
| "Email not received" | MAIL_DRIVER pointing to wrong service | Check MAIL_* vars, test with Mailtrap |
| "Modal doesn't open" | JavaScript error | Check browser console (DevTools → Console) |
| "404 on route" | Route not defined | Verify route in `routes/web.php`, clear cache: `php artisan route:clear` |
| "Database connection error" | DB credentials wrong | Verify `.env` DB_* vars match MySQL setup |

---

## 📚 Documentation Index

| Document | Purpose | Audience |
|----------|---------|----------|
| **LANDING_PAGE_SPEC.md** | Complete spec, copy, UX rules, integrations | Product, Eng, Design |
| **LANDING_PAGE_GUIDE.md** | Setup, customization, routes, validation | Engineers |
| **ENV_SETUP_GUIDE.md** | Environment variables & credentials | DevOps, Engineers |
| **This file** | Quick reference & checklist | Everyone |

---

## 🎓 Learning Resources

- **Laravel Docs**: https://laravel.com/docs
- **Tailwind CSS**: https://tailwindcss.com/docs
- **Africa's Talking**: https://africastalking.com/sms
- **Safaricom Daraja**: https://developer.safaricom.co.ke
- **Google Tag Manager**: https://tagmanager.google.com
- **Google Analytics**: https://analytics.google.com

---

## 👥 Team Contacts

| Role | Name | Contact |
|------|------|---------|
| Product Lead | [Name] | [Email] |
| Lead Engineer | [Name] | [Email] |
| Designer | [Name] | [Email] |
| DevOps | [Name] | [Email] |

---

## 📝 Changelog

| Date | Version | Changes |
|------|---------|---------|
| 2026-02-23 | 1.0 | Initial landing page, all sections, forms, routes |
| TBD | 1.1 | Africa's Talking USSD integration |
| TBD | 1.2 | M-Pesa B2C payouts sandbox |
| TBD | 2.0 | Live payments, production-ready |

---

## ✅ Pre-Launch Checklist

**Frontend:**
- [ ] All sections present (hero, features, pricing, FAQ, testimonials, CTA, footer)
- [ ] Mobile layout tested (320px, 480px, 768px, 1024px)
- [ ] Modals open/close correctly
- [ ] Form validation works
- [ ] Hero image displays
- [ ] Links work (internal anchors)
- [ ] Accessibility: tab through all buttons, test screen reader

**Backend:**
- [ ] Database migrations run (`php artisan migrate`)
- [ ] Phone validation works (9–13 digits)
- [ ] OTP generation & storage working
- [ ] OTP verification flow complete
- [ ] Demo form submits without errors
- [ ] User created after OTP verification
- [ ] Redirect to `/dashboard` works

**Analytics:**
- [ ] GTM container created & configured
- [ ] GTM ID added to `.env`
- [ ] GA4 property linked
- [ ] Events tracking (in browser console, Events tab in Firefox)

**Security:**
- [ ] CSRF tokens on all forms
- [ ] Input validation (client & server)
- [ ] Error messages generic (not revealing)
- [ ] OTP expires after 5 minutes
- [ ] Logs don't show sensitive data in production

**Performance:**
- [ ] Page loads in < 2 seconds
- [ ] Tailwind CSS compiled for production
- [ ] Images compressed
- [ ] No console errors (DevTools → Console)

**Deployment:**
- [ ] Code pushed to main branch
- [ ] `.env` configured with all required vars
- [ ] SSL/HTTPS enabled
- [ ] Error logging set up (Sentry or similar)
- [ ] Backups automated
- [ ] Monitoring alerts configured

---

**Last Updated**: 2026-02-23  
**Status**: MVP Ready  
**Questions?** See LANDING_PAGE_GUIDE.md or ENV_SETUP_GUIDE.md
