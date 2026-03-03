# SiteGrid Landing Page — Complete Specification & Implementation

**Date**: 2026-02-23  
**Status**: MVP Ready  
**Build**: Tailwind CSS + Laravel  

---

## Executive Summary

SiteGrid is a payroll & attendance platform for construction sites. The landing page converts site owners through a confidence-building narrative:
- **Value proposition** in 5 seconds (attendance, weekly pay, USSD, M-Pesa)
- **Trust signals** (pilot-ready, no bank account required)
- **Clear CTAs** (create site, request demo)
- **Mobile-first** design (feature-phone workers = feature-phone support)

---

## 1. Purpose & High-Level Goals

### Primary Goal
Convert site owners to signup via phone OTP. Measure success: **signup_initiated → otp_verified → site_created**

### Secondary Goals
1. Collect demo requests from mid-tier contractors
2. Build trust with pilot-customer testimonials
3. Establish brand authority (warm, accessible tone)
4. Seed analytics for acquisition channels

### Assumptions
- Target audience: small contractors (1–50 workers per site)
- Primary device: Android smartphone or feature phone with USSD
- Market: East Africa, Kenya first
- Legal: GDPR-lite (phone data minimal, secure)

---

## 2. Visual Identity & UX Principles

### Color Palette
| Element | Color | Hex | Use |
|---------|-------|-----|-----|
| Primary | Deep Indigo | #1e1b4b | Headers, text, trust |
| Accent | Warm Orange | #f97316 | CTAs, highlights, energy |
| Supporting | Gray | #6b7280 | Body text (70% gray-700) |
| Background | White / Off-white | #ffffff / #f9fafb | Clean, minimal |

### Typography
- **Headings** (H1–H3): Bold, size 2.25rem–3.75rem, indigo-900
- **Body text**: Regular, size 1rem, gray-700, 1.5 line-height
- **Small text**: 0.875rem, gray-600 (labels, help text)
- **Font stack**: System fonts (sans-serif) for performance

### Layout & Spacing
- **Max-width**: 7xl (80rem / 1280px)
- **Padding**: 4rem (py-16), scales down on mobile
- **Gap** (between sections): 3rem (gap-8 in Tailwind)
- **Mobile breakpoint**: 640px (sm), 768px (md), 1024px (lg)

### Interaction Design
- **Hover**: Buttons lift (scale-105) + brighten
- **Focus**: Outline (ring-2 ring-orange-500) on inputs
- **Loading**: Spinner inline in button text
- **Transition**: 200ms ease-out (smooth, not slow)
- **Modal**: Dark overlay (bg-black/50), center, click-outside to close

---

## 3. Page Sections & Copy (Verbatim)

### 3.1 Head Metadata (SEO)

```html
<title>SiteGrid — Payroll & Attendance for Construction Sites | Weekly Pay, USSD & M-Pesa</title>
<meta name="description" content="SiteGrid simplifies weekly payroll for construction sites: attendance capture, USSD for feature phones, and secure M-Pesa payouts. Start a site in minutes.">

<!-- Open Graph -->
<meta property="og:title" content="SiteGrid — Payroll & Attendance for Construction Sites | Weekly Pay, USSD & M-Pesa">
<meta property="og:description" content="SiteGrid simplifies weekly payroll for construction sites: attendance capture, USSD for feature phones, and secure M-Pesa payouts. Start a site in minutes.">
<meta property="og:image" content="https://sitegrid.local/images/og-hero.png">
<meta property="og:url" content="https://sitegrid.local/">

<!-- Twitter Card -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:title" content="SiteGrid — Payroll & Attendance for Construction Sites">
<meta property="twitter:description" content="Capture attendance, compute weekly pay, and pay workers using M-Pesa — with USSD for feature phones.">
<meta property="twitter:image" content="https://sitegrid.local/images/og-hero.png">
```

### 3.2 Header / Navigation (Sticky, Fixed-Top)

**Sticky**: Yes, z-index 50  
**Responsive**: Nav links hidden on mobile (< 768px), hamburger menu (not implemented yet)

**Elements:**
- **Logo** (left): M icon (indigo square) + "SiteGrid" text
- **Navigation** (center, desktop only):
  - "How it works" → #how-it-works
  - "Pricing" → #pricing
  - "FAQ" → #faq
- **CTAs** (right):
  - "Sign in" (text link, desktop only)
  - "Create a site" (button, orange, all devices)

### 3.3 Hero Section

**Headline (H1):**
> Payroll & attendance for real-life sites

**Subheadline:**
> Capture attendance, compute weekly pay, and pay workers using M-Pesa or the owner's account — with USSD for feature phones.

**CTAs:**
1. Primary: **"Create a site — it's free to try"** (orange, solid, onclick = openSignupModal)
2. Secondary: **"Request a demo"** (outline, indigo, href = #contact)

**Micro-trust line** (below CTAs, small gray text):
> ✓ Trusted by small contractors — pilot-ready. No bank account required.

**Hero Image:**
- Photo of small construction team (authentic, warm tones)
- Alternative: Stylized illustration of workers & checks
- Dimensions: 1200×628 (golden ratio-ish)
- Fallback: Gradient placeholder if image missing
- Hidden on mobile (< 768px) to save bandwidth

### 3.4 Feature Strip (3 Columns, Full-Width)

| Column | Title | Description |
|--------|-------|-------------|
| 1 | Attendance made easy | Foreman web + USSD check-in, QR optional. |
| 2 | Weekly payouts | Automatic pay-cycle calculation, B2C payouts to M-Pesa. |
| 3 | Pay everyone | Works with smartphones and feature phones via USSD. |

**Icon style:** Line icons (SVG), orange background (orange-100), size 2rem  
**Card hover**: Scale up slightly, add shadow

### 3.5 How It Works (3 Steps)

| Step | Title | Description |
|------|-------|-------------|
| 1 | Create a site | Add workers & set rates. Takes just a few minutes. |
| 2 | Mark attendance | Each day: foreman logs in web, or workers dial USSD code. |
| 3 | Compute & payout | Approve pay, payout via M-Pesa. All in one place. |

**Design:** Numbered circles (1, 2, 3), orange background, left border (orange), white background card

### 3.6 Pricing Section

**Header:**
> Simple, transparent pricing  
> No hidden fees. Scale as you grow.

**Pricing Card:**
```
KES 50
per worker / per week
Billed weekly
```

**Offer highlight** (badge, orange-100):
> 🎯 First 10 workers free for 4 weeks

**Features (bulleted):**
- Unlimited sites
- USSD + web access
- M-Pesa payouts
- Email support

**CTAs:**
1. "Start free trial" (primary, orange)
2. "View detailed pricing →" (text link, orange-600)

### 3.7 Testimonials & Social Proof

**3 short testimonials** (name, role, quote, avatar):

**Testimonial 1:**
> "SiteGrid cut our payroll time from 3 hours to 30 minutes every Friday. Workers get paid on time, we get reports."  
> **David Kipchoge** — Construction Foreman

**Testimonial 2:**
> "We run five sites and SiteGrid syncs everything. Especially love USSD — workers without smartphones can check in."  
> **Sarah Muthoni** — Site Manager

**Testimonial 3:**
> "No upfront fees, no minimum. Perfect for our pilot. We're scaling up to 40 workers next month."  
> **James Kariuki** — Project Owner

**Pilot CTA** (if no customers yet):
> 💼 Pilot partners welcome — [contact us to get started](#contact)

### 3.8 FAQ (Collapsible Details/Summary)

**Q1: How does USSD work?**  
A: Workers dial a code (e.g., `*123*456#`) to check in. They confirm their attendance, and we record it instantly. No data, no app, no smartphone required.

**Q2: How are payouts protected?**  
A: Site owners approve payroll before we send funds. Funds are held in an escrow until the owner releases them. All transactions are logged for audit and dispute resolution.

**Q3: What are payment fees?**  
A: Our platform charge is KES 50 per worker per week. M-Pesa transfer fees are paid by the site owner (standard Safaricom rates, ~KES 21–33 per transaction). USSD dialing is free on most networks.

**Q4: What if my workers don't have M-Pesa?**  
A: Most workers in Kenya have M-Pesa. If they don't, the site owner can payout manually, or we can help you set up a group account. Chat with sales for custom integrations.

**Q5: Can I trial SiteGrid for free?**  
A: Yes! Your first 10 workers are free for 4 weeks. No payment method needed to start. After that, it's KES 50 per worker per week.

### 3.9 Contact/Demo Section (Split Layout)

**Left side: Quick Signup**
> Create a site — it's free to try  
> Button: "Create a site"  
> Microcopy: "Free trial, no credit card."

**Right side: Demo Request Form**
> Request a Demo

**Form fields:**
1. Full Name (required, text)
2. Company (optional, text)
3. Email (required, email)
4. Phone (optional, tel)
5. Button: "Send Request"

**Backend handling:**
- Store lead in database
- Email to sales team (bcc: founder)
- Show thank-you toast: "Demo request sent! We'll contact you soon."

### 3.10 Footer

**5-column layout** (scales to 1 column on mobile):

| Brand | Product | Company | Legal | Social |
|-------|---------|---------|-------|--------|
| Logo + tagline | Features | Contact | Privacy Policy | Twitter |
| | Pricing | FAQ | Terms | LinkedIn |
| | How it works | Blog | Cookie Policy | GitHub |
| | Sign in | Docs | | |

**Bottom bar:**
```
© 2026 Mjengo. All rights reserved.

We'll only use your phone to send an OTP and important account updates. 
Read our Privacy Policy.
```

---

## 4. Forms & CTAs — Detailed Behavior

### 4.1 Primary Signup (Phone OTP Modal)

**Trigger:** "Create a site" button anywhere on page  
**Modal:** Centered, white background, shadow, 400px max-width

**Fields:**
1. **Phone number**
   - Placeholder: `+254 7XXXXXXXX`
   - Help text: "International format (e.g., +254 for Kenya)"
   - Validation (client): Required, 9–13 digits after country code
   - Validation (server): Regex for E.164 format, length

2. **Name** (optional)
   - Placeholder: `Your name`
   - Max length: 255 characters

**Behavior:**
- On submit: Show "Sending code..." spinner (button disabled)
- Delay: ~1–2s (simulating SMS gateway latency)
- Success: Display OTP modal (with masked phone)
- Error: Toast notification (red bg) with error message

**CSRF:** Use `@csrf` token in Blade

### 4.2 OTP Verification Modal

**Trigger:** Appears after phone submission  
**Display:** Modal shows masked phone (e.g., `+254 ••••••••`)

**Field:**
1. **OTP Code**
   - Placeholder: `000000`
   - Max length: 6 digits
   - Monospace font (mono)
   - Text-align: center
   - Help text: "Didn't get it? [Resend in 30s]"

**Behavior:**
- On submit: Show "Verifying..." spinner
- Delay: ~1–2s
- Success: Show "Account created! Redirecting to dashboard..." toast → redirect to /dashboard
- Error: Show error toast, stay on OTP modal
- **Resend button**: Disabled for 30s, countdown timer, then clickable

**Special handling:**
- Max 3 OTP attempts (rate limit)
- OTP expires after 5 minutes
- User can go back to phone entry (close modal, re-open)

### 4.3 Demo Request Form

**Trigger:** Bottom section (split layout) or "Request a demo" button  
**Inline form** (no modal)

**Fields:**
1. Full Name (required, text, max 255)
2. Company (optional, text, max 255)
3. Email (required, email)
4. Phone (optional, tel, max 20)
5. Message (optional, textarea, max 1000)

**Validation (server):**
- All required fields → 422 Unprocessable Entity
- Email format
- Spam check (optional: verify email domain exists)

**Behavior:**
- On submit: Show "Sending..." spinner
- Success: Clear form, show toast "Demo request sent! We'll contact you soon."
- Error: Show toast with error message
- **Email notification**: To lead captures email, subject "New demo request from {name}"

---

## 5. Imagery, Icons & Microinteractions

### 5.1 Images

| Location | Spec | Size | Format | Notes |
|----------|------|------|--------|-------|
| Hero | Construction team or illustration | 1200×628 | WebP + PNG | Authentic, warm tones |
| OG/Twitter | Same as hero | 1200×628 | WebP + PNG | Social preview |
| Favicon | SiteGrid logo (M square) | 32×32, 192×192 | PNG | Icon in browser tab |
| (Future) Partner logos | Safaricom, Google, etc. | Vary | PNG | Grayscale, 100px height |

**Optimization:**
- Compress via TinyPNG, ImageOptim
- WebP with PNG fallback
- `<img loading="lazy">` for non-critical images
- Max file size: 200KB per image

### 5.2 Icons (SVG)

**Icon set:** Heroicons (optional), or custom line icons  
**Color:** Orange (#f97316)  
**Size:** 24×24, 32×32 (Tailwind: w-6 h-6, w-8 h-8)  
**Style:** 2px stroke, rounded caps

**Icons used:**
- Clock (attendance)
- Wallet (payouts)
- Phone (USSD / feature phones)
- Check mark (success, trust)
- Chevron (expandable FAQ)
- Menu (mobile nav)

### 5.3 Animations & Microinteractions

| Interaction | Trigger | Animation | Duration | Easing |
|-------------|---------|-----------|----------|--------|
| CTA hover | Mouseover button | Scale 1.05 + brighten | 200ms | ease-out |
| Input focus | Focus on input | Ring-2 ring-orange-500 | 150ms | ease-out |
| Modal open | Click CTA | Fade-in overlay, scale-in modal | 300ms | ease-out |
| Modal close | Close button or Escape | Fade-out, reverse animation | 200ms | ease-in |
| Page scroll | Scroll down | Fade-in section (opacity 0→1) | 600ms | ease-out |
| Loading spinner | Form submit | Spinner in button | Loop | steady |
| Toast notification | Form success/error | Slide in from right | 200ms | ease-out |
| Resend countdown | OTP modal | Number decrement (1s interval) | N/A | sync |

### 5.4 Accessibility Tooltips

- Buttons: Title attribute for truncated text
- Forms: `aria-describedby` for help text
- Modals: `aria-modal="true"`, `role="alertdialog"`
- Links: Visible focus styles (not outline: none)

---

## 6. Accessibility & Performance Checklist

### 6.1 Accessibility (WCAG 2.1 AA)

- [ ] **Semantic HTML**: H1 → H2 → H3, landmarks (header, main, footer)
- [ ] **Form labels**: `<label for="...">` on all inputs
- [ ] **ARIA**: `aria-describedby` for error messages, `aria-label` for icons
- [ ] **Keyboard navigation**: Tab through all buttons/links, Escape to close modals
- [ ] **Focus styles**: Visible ring (min 2px, 3:1 contrast) on all interactive elements
- [ ] **Color contrast**: 4.5:1 for body text, 3:1 for large headings, 3:1 for icons
- [ ] **Alt text**: Descriptive alt on all images (not "image1.jpg")
- [ ] **Language**: `<html lang="en">`

### 6.2 Performance

- [ ] **Lighthouse Score**: >90 performance, >90 accessibility, >90 SEO
- [ ] **LCP** (Largest Contentful Paint): <2.5s
- [ ] **FID** (First Input Delay): <100ms
- [ ] **CLS** (Cumulative Layout Shift): <0.1
- [ ] **CSS**: Inline critical styles, async non-critical
- [ ] **JavaScript**: Defer non-critical scripts, no render-blocking JS
- [ ] **Images**: WebP + PNG, lazy-load non-critical, compress
- [ ] **Fonts**: System fonts or web fonts (max 2, subset to Latin)
- [ ] **Caching**: 30-day expires on static assets, CDN for images

### 6.3 Security

- [ ] **HTTPS**: Required, valid certificate
- [ ] **CSRF tokens**: On all forms (`@csrf` in Blade)
- [ ] **Input validation**: Server-side + client-side
- [ ] **Rate limiting**: Max 5 signup attempts/hour per IP
- [ ] **OTP expiry**: 5 minutes
- [ ] **Password hashing**: bcrypt, no plaintext storage
- [ ] **Error messages**: Generic ("Invalid code") not revealing internals
- [ ] **SQL injection**: Use parameterized queries (Laravel Eloquent native)
- [ ] **XSS prevention**: HTML escape output (Blade {{}} native)

---

## 7. Analytics & Tracking

### 7.1 Analytics Setup

**Tool**: Google Analytics 4 + Google Tag Manager (GTM)  
**Goals**:
- Understand signup funnel
- Track demo requests
- Identify acquisition channels
- Measure scroll depth (engagement)

### 7.2 Events to Track

| Event | Trigger | Params | Goal |
|-------|---------|--------|------|
| `signup_initiated` | Phone field submitted | `phone`, `timestamp` | Signup start |
| `otp_verified` | OTP code verified | `phone`, `duration` | Signup mid-point |
| `site_created` | User redirected to dashboard | `site_id`, `worker_count` | Signup complete |
| `demo_requested` | Demo form submitted | `email`, `company` | Sales lead |
| `cta_clicked` | Any primary button click | `button_text`, `section` | Engagement |

### 7.3 GTM Implementation

**In Blade (footer):**
```html
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-XXXXXX" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<!-- Google Tag Manager (head) -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0], j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-XXXXXX');</script>
```

**In JavaScript:**
```javascript
function trackEvent(eventName, eventData = {}) {
    if (window.gtag) {
        gtag('event', eventName, eventData);
    }
    console.log('Event tracked:', eventName, eventData);
}

// Track signup initiation
document.getElementById('signupFormPhone').addEventListener('submit', function() {
    trackEvent('signup_initiated', { method: 'phone' });
});
```

### 7.4 Conversion Pixels

- **Google Ads**: Conversion tracking for paid campaigns
- **Facebook Pixel**: If running FB ads (future)
- **Email platforms**: Segment, Klaviyo (future)

---

## 8. Legal & Privacy Microcopy

**Under phone field:**
> We'll only use your phone to send an OTP and important account updates. Read our Privacy Policy.

**Footer:**
> © 2026 SiteGrid. All rights reserved.  
> We'll only use your phone to send an OTP and important account updates. Read our Privacy Policy.

**Links:**
- Privacy Policy → `/privacy`
- Terms of Service → `/terms`
- Cookie Policy → `/cookies`

---

## 9. A/B Testing Ideas

### Test 1: CTA Copy

**Hypothesis**: "Free trial" language converts better than "free to try"  
**Control**: "Create a site — it's free to try"  
**Variant**: "Start your free pilot"  
**Metric**: Signup conversion rate (phone submitted)  
**Duration**: 2 weeks (minimum 30 conversions per arm)

### Test 2: Pricing Presentation

**Hypothesis**: Flat-rate pricing is clearer than % fee  
**Control**: KES 50 per worker per week  
**Variant**: KES 50 per site per week (flat)  
**Metric**: Upgrade to paid/support queries about pricing  
**Duration**: 1 week, expand based on results

### Test 3: Hero Image

**Hypothesis**: Real photo builds more trust than illustration  
**Control**: Authentic photo of construction team  
**Variant**: Stylized illustration (cartoon-ish)  
**Metric**: CTA click-through rate, time on page, scroll depth  
**Duration**: 1 week

---

## 10. Implementation Notes & Integrations

### Backend: Laravel

**Recommended packages:**
```bash
composer require laravel/sanctum # API tokens
composer require guzzlehttp/guzzle # HTTP requests
composer require nexmo/laravel # Nexmo SMS (optional)
composer require africastalking/africastalking # Africa's Talking
```

**Model: User (extend)**
```php
$table->string('phone')->unique();
$table->string('name')->nullable();
$table->string('email')->unique()->nullable();
$table->timestamp('phone_verified_at')->nullable();
```

### USSD Gateway: Africa's Talking

**Signup**: https://africastalking.com  
**Docs**: https://africastalking.com/sms  
**Credentials** (in .env):
```
AFRICASTALKING_USERNAME=your_username
AFRICASTALKING_API_KEY=your_api_key
AFRICASTALKING_SHORTCODE=12345 # For incoming USSD
```

**OTP sending** (replace in LandingController):
```php
$at = new AfricasTalking();
$sms = $at->sms();
$sms->send(
    "Your SiteGrid verification code is: $otp",
    [$phone]
);
```

### Payments: Safaricom Daraja (M-Pesa)

**Docs**: https://developer.safaricom.co.ke  
**APIs**:
- **B2C** (Business to Customer): Payout workers
- **C2B** (Customer to Business): Owner deposits (future)
- **STK Push**: Pop balance inquiry on worker phone (future)

**Sandbox credentials** (in .env):
```
SAFARICOM_CONSUMER_KEY=your_key
SAFARICOM_CONSUMER_SECRET=your_secret
SAFARICOM_PASSKEY=your_passkey
SAFARICOM_SHORTCODE=123456
SAFARICOM_ENVIRONMENT=sandbox # or production
```

### Email: Mailtrap or SendGrid

**Setup** (in .env):
```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=465
MAIL_USERNAME=your_user
MAIL_PASSWORD=your_pass
MAIL_FROM_ADDRESS=noreply@sitegrid.local
MAIL_FROM_NAME="SiteGrid"
```

**Send demo request email:**
```php
Mail::to(config('mail.from.address'))->send(
    new \App\Mail\DemoRequestNotification($validated)
);
```

---

## 11. Content Delivery & Image Guidelines

### 11.1 Image Specs

| Image | Dimensions | Format | Max Size | Notes |
|-------|-----------|--------|----------|-------|
| Hero | 1200×628 | WebP (primary), PNG (fallback) | 200KB | 16:9 aspect ratio |
| OG/Twitter | 1200×628 | Same as hero | 200KB | Use same image |
| Favicon | 32×32, 192×192 | PNG | 50KB total | SVG alternative |
| Partner logos | Vary (height 100px) | PNG | 50KB each | Grayscale |

### 11.2 Image Optimization

```bash
# Compress & convert to WebP
npx @squoosh/cli --webp '{"quality":80}' hero.jpg
optipng hero.png

# Convert to AVIF (next-gen)
npx @squoosh/cli --avif hero.jpg

# For srcset (responsive)
<img 
  src="hero.jpg" 
  srcset="hero-480w.jpg 480w, hero-800w.jpg 800w, hero-1200w.jpg 1200w"
  sizes="(max-width: 480px) 100vw, (max-width: 800px) 100vw, 1200px"
  alt="Construction team managing attendance"
/>
```

### 11.3 CDN & Caching

**Recommended**: Cloudflare (free tier), AWS CloudFront, or Bunny CDN

**Cache rules:**
- Images: 30 days
- CSS/JS: 1 year (versioned)
- HTML: 1 hour (or use ETags)

---

## 12. Deployment Checklist

### Pre-Deployment

- [ ] Environment variables set (`.env.production`)
- [ ] Database migrated (`php artisan migrate --env=production`)
- [ ] Tailwind compiled for production (`npm run build`)
- [ ] Static assets cached & versioned
- [ ] SSL certificate installed (HTTPS only)
- [ ] Error logging configured (Sentry, Bugsnag)
- [ ] Email service tested (demo form)
- [ ] Rate limiting configured
- [ ] CORS headers set if using API

### Launch Day

- [ ] Landing page loads in < 2 seconds
- [ ] Mobile viewport tested (Chrome DevTools)
- [ ] Hero image displays
- [ ] Signup modal works (phone → OTP → dashboard)
- [ ] Demo form emails sales team
- [ ] Google Analytics firing
- [ ] GTM container published
- [ ] Open Graph preview correct (Facebook Debugger)
- [ ] Twitter card correct (Twitter Card Validator)

### Post-Launch (Week 1)

- [ ] Monitor Lighthouse scores hourly
- [ ] Watch error logs (no 5xx)
- [ ] Track conversion funnel (signup → OTP → site created)
- [ ] Collect user feedback (demo requests, support)
- [ ] Fix bugs identified (responsive, form validation, etc.)

---

## 13. Performance Budgets

| Metric | Target | Current | Status |
|--------|--------|---------|--------|
| LCP | < 2.5s | TBD | - |
| FID | < 100ms | TBD | - |
| CLS | < 0.1 | TBD | - |
| Lighthouse Perf | > 90 | TBD | - |
| Lighthouse Access | > 90 | TBD | - |
| Lighthouse SEO | > 90 | TBD | - |
| Page Size | < 500KB | TBD | - |
| Requests | < 20 | TBD | - |

---

## 14. File Structure

```
mjengo/
├── app/
│   └── Http/Controllers/
│       └── LandingController.php       ← Form handlers
├── resources/
│   ├── views/
│   │   ├── landing.blade.php           ← Main landing page
│   │   ├── dashboard/
│   │   │   └── index.blade.php         ← Post-signup dashboard
│   │   └── auth/
│   │       └── login.blade.php
│   ├── css/
│   │   └── app.css                     ← Tailwind imports
│   └── js/
│       └── app.js                      ← Vue/React (optional)
├── routes/
│   └── web.php                         ← Routes
├── public/
│   └── images/
│       ├── hero-illustration.png
│       ├── og-hero.png
│       └── favicon.ico
├── LANDING_PAGE_GUIDE.md              ← Setup guide
├── LANDING_PAGE_SPEC.md               ← This file
└── vite.config.js                     ← Build config
```

---

## 15. Next Steps & Roadmap

### Immediate (This week)
1. Deploy landing page to production
2. Upload hero image
3. Set up Google Analytics & GTM
4. Test signup flow end-to-end
5. Monitor Lighthouse scores

### Sprint 1 (Week 2–3)
1. Integrate Africa's Talking USSD
2. Create onboarding wizard (site setup)
3. Switch OTP from session to database
4. Add rate limiting to signup endpoints
5. Set up email notifications

### Sprint 2 (Week 4–5)
1. Build foreman web dashboard
2. Integrate M-Pesa B2C payouts (sandbox)
3. Add worker management (CRUD)
4. Begin USSD attendance flow

### Sprint 3+ (Month 2)
1. Live M-Pesa payments
2. Full payroll computation
3. Admin analytics
4. Customer support dashboard

---

## 16. Contact & Support

### Team
- **Product**: [Add PM name]
- **Engineering**: [Add dev names]
- **Design**: [Add designer name]

### Resources
- Mjengo MVP Spec: `mjengoMVP.md`
- System Design: `system.md`
- Landing Page Setup: `LANDING_PAGE_GUIDE.md`

### External Docs
- [Laravel](https://laravel.com/docs)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Africa's Talking](https://africastalking.com)
- [Safaricom Daraja](https://developer.safaricom.co.ke)
- [Google Analytics](https://support.google.com/analytics)

---

**Document Version**: 1.0  
**Last Updated**: 2026-02-23  
**Status**: Ready for Implementation
