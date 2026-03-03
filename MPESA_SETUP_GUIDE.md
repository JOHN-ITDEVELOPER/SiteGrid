# M-Pesa Integration Setup Guide

## 🎉 Implementation Complete!

The SiteGrid platform now has full M-Pesa integration for wallet top-ups (STK Push) and automated worker withdrawals (B2C).

---

## 📋 What Was Built

### 1. **Database Structure**
- ✅ `owner_wallets` - Stores wallet balance for each site owner
- ✅ `wallet_transactions` - Tracks all credits/debits with before/after balance
- ✅ `mpesa_transactions` - Logs all M-Pesa API calls (STK Push & B2C)
- ✅ `payouts.mpesa_transaction_id` - Links payouts to M-Pesa transactions

### 2. **Models Created**
- ✅ `OwnerWallet` - With credit(), debit(), hasSufficientBalance() methods
- ✅ `WalletTransaction` - Transaction history with polymorphic references
- ✅ `MpesaTransaction` - M-Pesa API response tracking
- ✅ Updated `User` model with wallet() relationship
- ✅ Updated `Payout` model with mpesaTransaction() relationship

### 3. **Services & Controllers**
- ✅ `MpesaService` - Handles all M-Pesa API calls:
  - `stkPush()` - Initiates Lipa Na M-Pesa payment
  - `b2c()` - Sends money to workers
  - `stkQuery()` - Query transaction status
- ✅ `MpesaCallbackController` - Processes M-Pesa callbacks:
  - `stkCallback()` - Credits wallet on successful payment
  - `b2cCallback()` - Marks payout as paid/failed, refunds wallet if needed

### 4. **Owner Dashboard Features**
- ✅ Wallet page (`/owner/wallet`) showing:
  - Current balance
  - Pending payouts
  - Available balance
  - Top-up form with STK Push
  - Transaction history
  - Sites funding status
- ✅ Auto-create wallet for owners on first access
- ✅ Real-time transaction tracking

### 5. **Automated Withdrawal Flow**
**For Platform-Managed Sites:**
1. Owner approves withdrawal → System checks wallet balance
2. If sufficient → Deduct from wallet + Initiate M-Pesa B2C
3. Worker receives M-Pesa → Callback marks payout as 'paid'
4. If M-Pesa fails → Wallet automatically refunded

**For Owner-Managed Sites:**
- Simple approval (owner handles payment manually)

---

## 🔧 Configuration Required

### Step 1: Get M-Pesa Sandbox Credentials

1. Go to [Safaricom Daraja Portal](https://developer.safaricom.co.ke/)
2. Create an account and login
3. Create a new app (Sandbox)
4. Get your credentials:
   - Consumer Key
   - Consumer Secret
   - Passkey (test passkey provided below)
   - Shortcode: `174379` (sandbox)

### Step 2: Update Your `.env` File

Already configured with sandbox defaults. **Replace with your actual credentials:**

```env
MPESA_ENV=sandbox
MPESA_CONSUMER_KEY=your_consumer_key_from_daraja
MPESA_CONSUMER_SECRET=your_consumer_secret_from_daraja
MPESA_PASSKEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
MPESA_SHORTCODE=174379
MPESA_B2C_SHORTCODE=600000
MPESA_B2C_INITIATOR_NAME=testapi
MPESA_B2C_SECURITY_CREDENTIAL=your_b2c_credential_here
```

### Step 3: Configure Callback URLs

M-Pesa needs public URLs to send callbacks. **Options:**

#### Option A: Use Ngrok (for local testing)
```bash
ngrok http 80
```
Then use the ngrok URL:
- STK Push Callback: `https://your-ngrok-url.ngrok.io/api/mpesa/callback/stk`
- B2C Callback: `https://your-ngrok-url.ngrok.io/api/mpesa/callback/b2c`

#### Option B: Deploy to production server
- STK Push Callback: `https://yourdomain.com/api/mpesa/callback/stk`
- B2C Callback: `https://yourdomain.com/api/mpesa/callback/b2c`

**Note:** Callbacks are already registered in `routes/api.php` (no authentication required).

### Step 4: B2C Security Credential

For B2C payments, you need to generate a security credential:

1. Download the Daraja B2C certificate from [here](https://developer.safaricom.co.ke/APIs/BusinessToCustomer)
2. Use OpenSSL to encrypt your initiator password:

```bash
openssl pkcs12 -in <certificate.p12> -nocerts -out <privatekey.pem>
openssl rsa -in <privatekey.pem> -out <privatekey-nopass.pem>
echo -n 'YourInitiatorPassword' | openssl rsautl -encrypt -inkey <publickey.pem> -pubin | base64 -w 0
```

3. Copy the output and paste in `.env` as `MPESA_B2C_SECURITY_CREDENTIAL`

---

## 🧪 Testing the Integration

### Test STK Push (Wallet Top-Up)

1. Login as a site owner
2. Go to `/owner/wallet`
3. Enter amount (e.g., 100) and phone `254708374149` (sandbox test number)
4. Click "Initiate Top-up"
5. Sandbox will simulate STK push (no actual phone prompt in sandbox)
6. Check logs: `storage/logs/laravel.log` for callback

**Sandbox Test Numbers:**
- `254708374149` - Always successful
- `254708374150` - Always fails
- `254708374151` - Timeout

### Test B2C (Worker Withdrawal)

1. Create a payout for a worker
2. Owner approves the withdrawal
3. System checks wallet balance
4. If sufficient, initiates M-Pesa B2C
5. Check `mpesa_transactions` table for transaction record
6. Callback updates payout status to 'paid'

**Monitor:**
```sql
SELECT * FROM mpesa_transactions ORDER BY created_at DESC;
SELECT * FROM wallet_transactions ORDER BY created_at DESC;
SELECT * FROM owner_wallets;
```

---

## 📂 Key Files Modified/Created

### New Files
- `app/Services/MpesaService.php` - M-Pesa API integration
- `app/Http/Controllers/MpesaCallbackController.php` - Callback handler
- `app/Models/OwnerWallet.php` - Wallet model
- `app/Models/WalletTransaction.php` - Transaction model
- `app/Models/MpesaTransaction.php` - M-Pesa transaction model
- `database/migrations/*_create_owner_wallets_table.php`
- `database/migrations/*_create_wallet_transactions_table.php`
- `database/migrations/*_create_mpesa_transactions_table.php`
- `database/migrations/*_add_mpesa_transaction_id_to_payouts_table.php`

### Modified Files
- `config/services.php` - Added M-Pesa configuration
- `.env` - Added M-Pesa credentials
- `routes/api.php` - Added callback routes
- `app/Http/Controllers/Owner/DashboardController.php` - Updated wallet() and initiateTopup(), modified approveClaim() for auto-disbursement
- `app/Models/User.php` - Added wallet() relationship
- `app/Models/Payout.php` - Added mpesa_transaction_id and relationship
- `resources/views/owner/wallet.blade.php` - Updated UI to show real wallet data

---

## 🎯 How It Works

### Wallet Top-Up Flow (STK Push)

```
1. Owner clicks "Initiate Top-up" with amount + phone
   ↓
2. MpesaService->stkPush() called
   ↓
3. M-Pesa API sends STK push to phone
   ↓
4. Creates pending mpesa_transaction record
   ↓
5. User enters M-Pesa PIN on phone
   ↓
6. M-Pesa calls /api/mpesa/callback/stk
   ↓
7. Callback credits owner_wallet
   ↓
8. Creates wallet_transaction (credit)
   ↓
9. Updates mpesa_transaction to 'completed'
```

### Worker Withdrawal Flow (B2C)

```
1. Owner approves worker withdrawal
   ↓
2. System checks site payout_method
   ↓
3. If 'platform_managed':
   - Check wallet balance
   - If sufficient: debit wallet
   - Call MpesaService->b2c()
   ↓
4. M-Pesa API sends money to worker phone
   ↓
5. Creates pending mpesa_transaction record
   ↓
6. M-Pesa processes payment
   ↓
7. M-Pesa calls /api/mpesa/callback/b2c
   ↓
8. If successful:
   - Update payout status to 'paid'
   - Log M-Pesa receipt number
   ↓
9. If failed:
   - Update payout status to 'failed'
   - Refund wallet (credit back)
   - Log error message
```

---

## 🚨 Important Notes

### Sandbox vs Production

**Sandbox:**
- No real money transactions
- Simulated responses
- Test phone numbers only
- No actual STK push on phone

**Production:**
- Real money transactions
- Real phone prompts
- Requires business account approval
- Must set `MPESA_ENV=production`

### Security

1. **Never commit `.env` to git** - Already in `.gitignore`
2. **Callbacks are public** - M-Pesa doesn't support auth headers. Validate callback data thoroughly.
3. **B2C security credential** - Keep secure, never expose in logs

### Wallet Balance Management

- Wallet balance is atomic (uses DB transactions)
- Always check `hasSufficientBalance()` before debiting
- Auto-refund on failed B2C maintains consistency
- Transaction history immutable (audit trail)

### Error Handling

All errors are logged to `storage/logs/laravel.log`:
- M-Pesa API failures
- Callback processing issues
- Wallet operations
- B2C payment failures

**Monitor logs regularly:**
```bash
tail -f storage/logs/laravel.log
```

---

## 📊 Database Schema Reference

### owner_wallets
```sql
id, user_id (FK), balance (decimal), currency (default: KES), created_at, updated_at
```

### wallet_transactions
```sql
id, wallet_id (FK), type (credit/debit), amount, balance_before, balance_after, 
reference_type, reference_id, description, created_at, updated_at
```

### mpesa_transactions
```sql
id, transaction_type (stk_push/b2c), merchant_request_id, checkout_request_id,
conversation_id, originator_conversation_id, mpesa_receipt_number, phone_number,
amount, result_code, result_description, status, related_model, related_id,
raw_response (JSON), created_at, updated_at
```

---

## 🔄 Next Steps (Optional Enhancements)

1. **Admin Dashboard** - Show platform-wide M-Pesa transaction stats
2. **Auto Top-up** - Trigger STK push when wallet balance low
3. **Batch Payments** - Process multiple B2C payments in one go
4. **SMS Notifications** - Notify workers on successful payment
5. **Transaction Receipts** - Generate PDF receipts with M-Pesa details
6. **Webhook Retry Logic** - Retry failed callbacks automatically
7. **C2B Integration** - Allow direct payments without STK push
8. **Balance Alerts** - Email owner when wallet balance < threshold

---

## 🛠️ Troubleshooting

### Issue: STK Push not received

**Cause:** Using sandbox with real phone number
**Solution:** Use test numbers (254708374149) or deploy to production

### Issue: Callback not working

**Cause:** M-Pesa can't reach localhost
**Solution:** Use ngrok or deploy to public server

### Issue: B2C payment fails

**Cause:** Incorrect security credential or insufficient B2C float
**Solution:** 
- Verify security credential generation
- Check Daraja portal for error details
- Ensure B2C shortcode has funds (production only)

### Issue: Wallet not created

**Cause:** User model doesn't have wallet relationship loaded
**Solution:** Already fixed - auto-creates on first access

### Issue: Insufficient balance error

**Cause:** Wallet balance < payout amount
**Solution:** Owner needs to top up wallet via STK Push

---

## 📞 Support & Documentation

- **Safaricom Daraja Docs:** https://developer.safaricom.co.ke/Documentation
- **Test Credentials:** https://developer.safaricom.co.ke/test_credentials
- **API Reference:** https://developer.safaricom.co.ke/APIs

---

## ✅ Checklist for Going Live

- [ ] Get production M-Pesa credentials from Safaricom
- [ ] Update `.env` with production keys
- [ ] Set `MPESA_ENV=production`
- [ ] Register production callback URLs with Safaricom
- [ ] Generate production B2C security credential
- [ ] Test with small amounts first
- [ ] Set up monitoring and alerting
- [ ] Configure automatic backups for wallet tables
- [ ] Implement transaction reconciliation reports

---

**Implementation Date:** February 28, 2026  
**Status:** ✅ Complete and ready for testing  
**Environment:** Sandbox (switch to production when ready)
