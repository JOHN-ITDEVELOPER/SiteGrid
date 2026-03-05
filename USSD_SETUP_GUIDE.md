# USSD Feature Setup Guide

## Overview

The SiteGrid USSD feature allows workers to claim their funds using basic feature phones by dialing a USSD code (e.g., `*384*12345#`). This is integrated with Africa's Talking USSD Gateway and SMS API.

---

## Features

Workers can dial the USSD code to:
1. **Check Balance** - View available funds per site
2. **Claim Pay** - Request withdrawal from any site
3. **View Attendance** - See attendance summary and last payout
4. **Get Help** - View support information

---

## Architecture

```
Worker dials *384*12345#
    ↓
Africa's Talking sends POST to /api/ussd/callback
    ↓
UssdController validates & routes to UssdService
    ↓
UssdService handles menu logic & session state
    ↓
Creates WorkerClaim with source='ussd'
    ↓
Auto-disburse OR awaits owner approval
    ↓
M-Pesa B2C payment sent to worker
```

---

## Setup Instructions

### 1. Africa's Talking Account Setup

1. **Sign up**: https://africastalking.com/
2. **Get credentials**:
   - Username (usually `sandbox` for testing)
   - API Key (from dashboard)
3. **Configure USSD**:
   - Go to USSD → Launch USSD Code
   - Purchase or use sandbox code
   - Set webhook URL: `https://yourdomain.com/api/ussd/callback`

### 2. Environment Configuration

Add to your `.env` file:

```env
# Africa's Talking Configuration
AFRICASTALKING_USERNAME=sandbox
AFRICASTALKING_API_KEY=your_api_key_here
AFRICASTALKING_FROM=SITEGRID
AFRICASTALKING_ENV=sandbox
```

For production:
```env
AFRICASTALKING_USERNAME=your_username
AFRICASTALKING_API_KEY=your_production_api_key
AFRICASTALKING_FROM=SITEGRID
AFRICASTALKING_ENV=production
```

### 3. Webhook Configuration

In Africa's Talking Dashboard:
- **USSD Callback URL**: `https://yourdomain.com/api/ussd/callback`
- **HTTP Method**: POST
- **Content-Type**: application/x-www-form-urlencoded

---

## USSD Menu Flow

### Main Menu
```
Welcome to SiteGrid, [Worker Name]

1. My Balance
2. Claim Pay
3. Attendance Summary
4. Help
```

### 1. My Balance
Shows available funds per site:
```
YOUR BALANCE

Construction Site A
Available: KES 5,250.00

Farm Project B
Available: KES 2,100.00

0. Back to Menu
```

### 2. Claim Pay

**Step 1**: Select site
```
CLAIM PAY - Select Site

1. Construction Site A
   KES 5,250.00

2. Farm Project B
   KES 2,100.00

0. Cancel
```

**Step 2**: Enter amount
```
CLAIM PAY - Construction Site A

Available: KES 5,250.00

Enter amount to claim:
(or 0 to cancel)
```

**Step 3**: Confirm
```
CONFIRM WITHDRAWAL

Site: Construction Site A
Amount: KES 2,000.00

1. Confirm
0. Cancel
```

**Step 4**: Success
```
SUCCESS!

Withdrawal processing...
Amount: KES 2,000.00
You'll receive payment shortly.

Ref: #12345
```

### 3. Attendance Summary
```
ATTENDANCE SUMMARY

Construction Site A
This Week: 5 days
Last Paid: Feb 28
Amount: KES 3,500.00

0. Back to Menu
```

### 4. Help
```
HELP

SiteGrid Help:
- Balance: Check available funds
- Claim Pay: Request withdrawal
- Attendance: View work summary

Support: 0700123456
Email: support@sitegrid.co.ke

0. Back to Menu
```

---

## Testing in Development

### Using the Simulator

Test USSD flows without Africa's Talking:

```bash
# Test main menu
curl -X POST http://localhost:8000/api/ussd/simulate \
  -H "Content-Type: application/json" \
  -d '{"phone": "254712345678", "text": ""}'

# Test balance check (option 1)
curl -X POST http://localhost:8000/api/ussd/simulate \
  -H "Content-Type: application/json" \
  -d '{"phone": "254712345678", "text": "1"}'

# Test claim pay flow (option 2, site 1, amount 1000, confirm)
curl -X POST http://localhost:8000/api/ussd/simulate \
  -H "Content-Type: application/json" \
  -d '{"phone": "254712345678", "text": "2*1*1000*1"}'
```

### Testing with Africa's Talking Sandbox

1. Use sandbox USSD code (e.g., `*384*12345#`)
2. Dial from a test phone number registered in sandbox
3. Check logs: `tail -f storage/logs/laravel.log | grep USSD`

---

## Database Schema

### Worker Claims with USSD Source

```sql
worker_claims
  - source ENUM('web', 'api', 'ussd')
```

USSD claims are created with `source='ussd'` and follow the same approval/disbursement flow as web claims.

---

## Session Management

- **Cache Driver**: Uses Laravel Cache (Redis recommended for production)
- **Session Timeout**: 120 seconds (2 minutes)
- **Session Key**: `ussd_session_{sessionId}`
- **State Stored**: Site selections, amounts, navigation state

### Clear Stale Sessions

```bash
php artisan cache:clear
```

Or schedule cache cleanup:
```php
// app/Console/Kernel.php
$schedule->command('cache:clear')->daily();
```

---

## Logging & Monitoring

### Log Locations

All USSD requests/responses are logged:
```php
Log::info('USSD Request', [
    'sessionId' => $sessionId,
    'phoneNumber' => $phoneNumber,
    'text' => $text,
]);
```

View logs:
```bash
tail -f storage/logs/laravel.log | grep "USSD"
```

### Monitoring Endpoints

**Statistics** (admin only):
```
GET /api/ussd/statistics
```

Returns:
```json
{
  "total_sessions": 1250,
  "active_sessions": 12,
  "completed_sessions": 1180,
  "failed_sessions": 58,
  "avg_session_duration": 45
}
```

---

## Security Considerations

### 1. Worker Authentication
- Workers identified by phone number
- Phone must be registered in system
- No password required (phone-based auth)

### 2. Rate Limiting
Add rate limiting to webhook:
```php
Route::post('/ussd/callback', [UssdController::class, 'handleRequest'])
    ->middleware('throttle:60,1'); // 60 requests per minute
```

### 3. Webhook Validation
Africa's Talking doesn't provide signature validation for USSD. Consider:
- IP whitelisting
- API key verification
- Request validation

---

## Troubleshooting

### Worker Not Found
**Error**: "Your phone number is not registered"
**Fix**: Ensure worker's phone is in `users` table in format `254XXXXXXXXX`

### Session Timeout
**Error**: Session state lost during claim
**Fix**: 
- Check cache driver is working
- Increase session timeout if needed
- Use Redis for production

### SMS Not Sending
**Error**: OTP SMS not received
**Fix**:
- Verify Africa's Talking credentials
- Check SMS balance in dashboard
- Review logs for API errors

### Balance Shows Zero
**Error**: Worker sees KES 0.00
**Fix**:
- Check attendance records exist
- Verify attendance status is 'present'
- Ensure no pending claims

---

## Production Deployment

### Pre-deployment Checklist

- [ ] Update `.env` with production credentials
- [ ] Set `AFRICASTALKING_ENV=production`
- [ ] Configure production USSD shortcode
- [ ] Set webhook URL to production domain
- [ ] Enable HTTPS for webhook
- [ ] Configure Redis for cache
- [ ] Set up monitoring/alerting
- [ ] Test full flow in production sandbox
- [ ] Train workers on USSD usage

### Webhook URL Format
```
https://yourdomain.com/api/ussd/callback
```

Must be:
- HTTPS (required by Africa's Talking)
- Publicly accessible
- Fast response time (<5 seconds)

---

## SMS Notifications

Workers automatically receive SMS for:
- **OTP verification** during signup
- **Payment confirmations** after successful B2C
- **Claim status updates** (approved/rejected)

Example SMS:
```
Your SiteGrid withdrawal of KES 2,000.00 has been processed. 
M-Pesa receipt: UC2KQ8HN3J. 
Thank you!
```

---

## Cost Estimates

### Africa's Talking Pricing (Kenya)

- **USSD Sessions**: ~KES 0.50 per session
- **SMS (Bulk)**: ~KES 0.50 - 1.00 per SMS
- **Monthly Fee**: KES 500 (USSD shortcode)

### Example Monthly Cost (100 workers)
- USSD sessions: 100 workers × 4 sessions/month × KES 0.50 = KES 200
- SMS (OTP): 100 workers × 2 SMS/month × KES 1.00 = KES 200
- Shortcode fee: KES 500
- **Total**: ~KES 900/month

---

## API Reference

### USSD Callback Endpoint

**POST** `/api/ussd/callback`

**Request Parameters** (from Africa's Talking):
```
sessionId: string (unique session identifier)
phoneNumber: string (e.g., +254712345678)
text: string (user input, * separated)
serviceCode: string (e.g., *384*12345#)
networkCode: string (telco code)
```

**Response Format**:
```
CON [message]  // Continue session
END [message]  // End session
```

### Simulator Endpoint

**POST** `/api/ussd/simulate` (Development only)

**Request Body**:
```json
{
  "phone": "254712345678",
  "text": "2*1*1000*1"
}
```

**Response**:
```json
{
  "success": true,
  "sessionId": "SIM-abc123",
  "type": "end",
  "response": "END SUCCESS!\\n\\nWithdrawal processing...\\nAmount: KES 1,000.00\\n...",
  "displayText": "SUCCESS!\\n\\nWithdrawal processing..."
}
```

---

## Future Enhancements

- [ ] Multi-language support (Swahili, etc.)
- [ ] PIN-based security for large withdrawals
- [ ] Transaction history via USSD
- [ ] Site-specific announcements
- [ ] Foreman attendance marking via USSD
- [ ] Balance transfer between sites
- [ ] Bill payments integration

---

## Support

**Technical Issues**: support@sitegrid.co.ke  
**Africa's Talking Support**: https://help.africastalking.com/  
**Documentation**: https://developers.africastalking.com/docs/ussd/overview

---

## License

This USSD implementation is part of the SiteGrid platform and follows the same license terms.
