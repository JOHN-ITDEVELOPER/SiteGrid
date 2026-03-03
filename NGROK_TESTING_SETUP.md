# Ngrok Testing Setup Documentation

## What Was Changed for Ngrok Testing

### 1. Environment Configuration (.env)

**Changes Made:**
```env
# Changed from: APP_URL=http://localhost/sitegrid/public
# Changed to:
APP_URL=https://2664-102-210-173-182.ngrok-free.app

# Added:
SESSION_SECURE_COOKIE=false
```

**Why:**
- `APP_URL` must point to the ngrok HTTPS URL for M-Pesa callbacks to work
- M-Pesa Sandbox rejects localhost URLs and requires HTTPS
- `SESSION_SECURE_COOKIE=false` allows sessions to work over ngrok (which uses HTTPS but sessions need special config)

---

### 2. Proxy Trust Configuration (app/Http/Middleware/TrustProxies.php)

**Changes Made:**
```php
// Changed from: protected $proxies;
// Changed to:
protected $proxies = '*';
```

**Why:**
- Ngrok acts as a reverse proxy server
- Laravel needs to trust ngrok's proxy headers to correctly handle:
  - HTTPS detection
  - Session cookies
  - CSRF token validation
  - Correct URL generation
- Without this, login fails because Laravel doesn't recognize the HTTPS connection

---

### 3. M-Pesa Service Callback URLs (app/Services/MpesaService.php)

**Changes Made:**
```php
// STK Push callback (Line ~83)
// Changed from: $callbackUrl = url(route('mpesa.callback.stk'));
// Changed to:
$callbackUrl = config('app.url') . '/sitegrid/public/api/mpesa/callback/stk';

// B2C callback (Line ~163)
// Changed from: $callbackUrl = url(route('mpesa.callback.b2c'));
// Changed to:
$callbackUrl = config('app.url') . '/sitegrid/public/api/mpesa/callback/b2c';

// Added logging:
Log::info('STK Push Callback URL: ' . $callbackUrl);
```

**Why:**
- Ensures M-Pesa receives the full absolute URL including the subdirectory path
- `config('app.url')` reads from APP_URL setting
- Manual path construction ensures correct URL structure: `https://domain.com/sitegrid/public/api/mpesa/callback/stk`

---

## How to Roll Back for Production

### Step 1: Update Environment Variables

Create/update your production `.env` file:

```env
# Use your production domain with SSL certificate
APP_URL=https://yourdomain.com

# Enable secure cookies for production
SESSION_SECURE_COOKIE=true
SESSION_SECURE_COOKIE=

# Update M-Pesa to PRODUCTION credentials
MPESA_ENV=production
MPESA_CONSUMER_KEY=your_production_consumer_key
MPESA_CONSUMER_SECRET=your_production_consumer_secret
MPESA_PASSKEY=your_production_passkey
MPESA_SHORTCODE=your_production_shortcode
MPESA_B2C_SHORTCODE=your_production_b2c_shortcode
MPESA_B2C_INITIATOR_NAME=your_production_initiator
MPESA_B2C_SECURITY_CREDENTIAL=your_production_security_credential
```

**Important Production Notes:**
- Your production domain MUST have a valid SSL certificate (HTTPS)
- M-Pesa production credentials are different from sandbox
- Get production credentials from Safaricom after going live
- B2C Security Credential must be generated using Safaricom's public certificate

---

### Step 2: Update MpesaService.php for Production

**Option A: If your production app is at root domain (e.g., https://yourdomain.com)**

```php
// In app/Services/MpesaService.php

// STK Push callback (~Line 83)
$callbackUrl = config('app.url') . '/api/mpesa/callback/stk';

// B2C callback (~Line 163)
$callbackUrl = config('app.url') . '/api/mpesa/callback/b2c';
```

**Option B: If your production app is in a subdirectory (e.g., https://yourdomain.com/app)**

```php
// If production is at: https://yourdomain.com/app
// Set in .env: APP_URL=https://yourdomain.com/app

// STK Push callback (~Line 83)
$callbackUrl = config('app.url') . '/api/mpesa/callback/stk';

// B2C callback (~Line 163)
$callbackUrl = config('app.url') . '/api/mpesa/callback/b2c';
```

**Option C: Use Laravel's route helpers (Recommended for clean URLs)**

```php
// STK Push callback
$callbackUrl = url(route('mpesa.callback.stk'));

// B2C callback
$callbackUrl = url(route('mpesa.callback.b2c'));
```

This works if your `APP_URL` is set correctly and routes are properly registered.

---

### Step 3: Update Proxy Trust Configuration (Optional but Recommended)

For production behind a reverse proxy (like Nginx, Cloudflare, AWS ELB):

**app/Http/Middleware/TrustProxies.php:**

```php
// Option 1: Trust specific proxy IPs (most secure)
protected $proxies = [
    '192.168.1.1',  // Your proxy server IP
    '10.0.0.0/8',   // Private network range
];

// Option 2: Trust all proxies (if using CDN like Cloudflare)
protected $proxies = '*';

// Option 3: Trust no proxies (if direct server connection)
protected $proxies;
```

**For most production environments with Cloudflare/CDN, keep `'*'`**

---

### Step 4: Clear Caches on Production

After deploying changes, run:

```bash
php artisan config:cache
php artisan cache:clear
php artisan route:cache
php artisan view:cache
```

---

## Quick Reference: Testing vs Production

| Configuration | Testing (Ngrok) | Production |
|--------------|-----------------|------------|
| **APP_URL** | `https://xxxx.ngrok-free.app` | `https://yourdomain.com` |
| **SESSION_SECURE_COOKIE** | `false` | `true` (or omit) |
| **Trusted Proxies** | `'*'` | `'*'` or specific IPs |
| **MPESA_ENV** | `sandbox` | `production` |
| **Callback URLs** | Full path with subdirectory | Clean URL from APP_URL |
| **M-Pesa Credentials** | Sandbox keys | Production keys |
| **SSL Certificate** | Ngrok provides | Required on server |

---

## Testing the Production Setup

### 1. Verify Callback URLs

Add temporary logging to see what M-Pesa receives:

```php
// In MpesaService.php stkPush() method
Log::info('STK Push Callback URL: ' . $callbackUrl);
Log::info('Full Payload: ', $payload);
```

Check `storage/logs/laravel.log` to confirm URLs are correct.

### 2. Test STK Push Flow

1. Initiate a small test payment (e.g., 10 KES)
2. Check logs for successful API call
3. Verify callback is received
4. Confirm wallet is credited

### 3. Test B2C Flow

1. Create a test payout
2. Approve it from owner dashboard
3. Verify wallet is debited
4. Check M-Pesa callback confirms payment
5. Confirm payout status updates to "paid"

---

## Rollback Checklist

Before going to production:

- [ ] Update `APP_URL` to production domain
- [ ] Enable `SESSION_SECURE_COOKIE=true`
- [ ] Update all `MPESA_*` credentials to production values
- [ ] Test callback URLs resolve correctly (use curl to test endpoint access)
- [ ] Register production callback URLs with Safaricom (if required)
- [ ] Generate B2C Security Credential using production certificate
- [ ] Update `TrustProxies.php` if needed for your hosting environment
- [ ] Update MpesaService callback URL construction (remove hardcoded subdirectory if needed)
- [ ] Run cache clear commands after deployment
- [ ] Test login, sessions, and CSRF tokens work
- [ ] Test M-Pesa integration with small amounts first
- [ ] Monitor logs for any errors
- [ ] Set up proper error alerting (email/Slack notifications)

---

## Common Production Issues

### Issue: "Invalid CallBackURL" in Production

**Solution:**
- Verify SSL certificate is valid and not self-signed
- Ensure callback URLs are publicly accessible (test with curl from external server)
- Check firewall allows incoming connections on port 443
- Confirm APP_URL matches actual domain

### Issue: Login not working in Production

**Solution:**
- Verify `SESSION_SECURE_COOKIE=true` for HTTPS
- Check `TrustProxies` is correctly configured
- Clear browser cookies
- Verify HTTPS is working (not mixed content warnings)

### Issue: CSRF Token Mismatch

**Solution:**
- Ensure `APP_URL` matches the domain users access
- Verify proxy headers are trusted
- Check session driver is working (file/database/redis)
- Clear `storage/framework/sessions/*` if needed

---

## Ngrok URL Updates

**Note:** Ngrok free tier generates new URLs each time you restart. When you get a new URL:

1. Update `.env`:
   ```env
   APP_URL=https://new-url.ngrok-free.app
   ```

2. Clear caches:
   ```bash
   php artisan config:cache
   ```

3. Clear browser cookies/use incognito window

---

## Support & Resources

- **M-Pesa Daraja API Docs:** https://developer.safaricom.co.ke/
- **Laravel Proxy Documentation:** https://laravel.com/docs/requests#configuring-trusted-proxies
- **Ngrok Documentation:** https://ngrok.com/docs
- **SSL Certificate (Let's Encrypt):** https://letsencrypt.org/

For production deployment assistance, consult with your hosting provider about:
- SSL certificate installation
- Reverse proxy configuration
- Firewall rules for M-Pesa callbacks
- Server security best practices
