# M-Pesa Quick Test Guide

## 🧪 Testing Your M-Pesa Integration

### Prerequisites
1. Update `.env` with your Daraja credentials (consumer key & secret)
2. For local testing, run ngrok: `ngrok http 80`
3. Ensure migrations have run: `php artisan migrate`

---

## Test #1: Wallet Creation

**Test automatic wallet creation for site owner:**

```bash
# Login as a site owner account and visit:
http://localhost/sitegrid/public/owner/wallet

# Expected Result:
# - Wallet auto-created with KES 0.00 balance
# - Shows "Current Balance: KES 0.00"
# - Shows "Pending Payouts: KES 0.00"
# - Shows top-up form
```

**Database Check:**
```sql
SELECT * FROM owner_wallets WHERE user_id = [YOUR_OWNER_USER_ID];
```

---

## Test #2: STK Push (Wallet Top-Up)

### Using Sandbox Test Numbers

**Step 1:** Login as site owner and go to `/owner/wallet`

**Step 2:** Fill in the top-up form:
- Amount: `100`
- Phone: `254708374149` (Sandbox success number)

**Step 3:** Click "Initiate Top-up"

**Expected Result:**
- Success message shown
- `mpesa_transactions` table has new record with status 'pending'
- In sandbox, callback is simulated (may need to manually trigger)

**Database Check:**
```sql
SELECT * FROM mpesa_transactions ORDER BY created_at DESC LIMIT 1;
-- Should show: transaction_type='stk_push', status='pending'

SELECT * FROM wallet_transactions ORDER BY created_at DESC LIMIT 1;
-- Should show credit transaction AFTER callback processed
```

### Sandbox Test Phone Numbers
- `254708374149` → Success
- `254708374150` → Failure (insufficient funds)
- `254708374151` → Timeout

---

## Test #3: Manual Callback Simulation (For Local Testing)

Since callbacks need public URL, you can simulate them:

### Simulate Successful STK Push Callback

**Find your checkout_request_id:**
```sql
SELECT checkout_request_id FROM mpesa_transactions WHERE transaction_type='stk_push' ORDER BY created_at DESC LIMIT 1;
```

**Send POST request to callback:**
```bash
curl -X POST http://localhost/sitegrid/public/api/mpesa/callback/stk \
-H "Content-Type: application/json" \
-d '{
  "Body": {
    "stkCallback": {
      "MerchantRequestID": "29115-34620561-1",
      "CheckoutRequestID": "YOUR_CHECKOUT_REQUEST_ID_HERE",
      "ResultCode": 0,
      "ResultDesc": "The service request is processed successfully.",
      "CallbackMetadata": {
        "Item": [
          {"Name": "Amount", "Value": 100},
          {"Name": "MpesaReceiptNumber", "Value": "TEST123456"},
          {"Name": "PhoneNumber", "Value": 254708374149}
        ]
      }
    }
  }
}'
```

**Verify wallet credited:**
```sql
SELECT * FROM owner_wallets WHERE user_id = [YOUR_OWNER_ID];
-- Balance should be 100.00

SELECT * FROM wallet_transactions WHERE type='credit' AND reference_type='top_up';
-- Should show credit transaction
```

---

## Test #4: Worker Withdrawal with Wallet

### Setup
1. Ensure owner has wallet balance > 0 (top up first)
2. Create a site with `payout_method = 'platform_managed'`
3. Create a worker on that site
4. Create a pay cycle with a payout for the worker

### Test Flow

**Step 1:** Login as site owner

**Step 2:** Go to `/owner/claims` (or wherever payouts are approved)

**Step 3:** Approve a payout for amount less than wallet balance

**Expected Result:**
- Success message: "Claim approved and payment initiated via M-Pesa"
- Wallet balance deducted
- `mpesa_transactions` has B2C entry with status 'pending'
- `payouts` table shows status = 'processing'

**Database Check:**
```sql
-- Check wallet was debited
SELECT * FROM wallet_transactions WHERE type='debit' AND reference_type='WorkerClaim';

-- Check M-Pesa B2C transaction created
SELECT * FROM mpesa_transactions WHERE transaction_type='b2c' ORDER BY created_at DESC LIMIT 1;

-- Check payout status
SELECT status, mpesa_transaction_id FROM payouts ORDER BY updated_at DESC LIMIT 1;
-- Should show: status='processing'
```

### Test Insufficient Balance

**Step 1:** Try to approve payout with amount > wallet balance

**Expected Result:**
- Error message: "Insufficient wallet balance. Required: KES XXX, Available: KES YYY"
- No wallet deduction
- No M-Pesa transaction created

---

## Test #5: B2C Callback Processing

### Simulate Successful B2C Callback

**Find your conversation_id:**
```sql
SELECT conversation_id FROM mpesa_transactions WHERE transaction_type='b2c' ORDER BY created_at DESC LIMIT 1;
```

**Send POST request:**
```bash
curl -X POST http://localhost/sitegrid/public/api/mpesa/callback/b2c \
-H "Content-Type: application/json" \
-d '{
  "Result": {
    "ConversationID": "YOUR_CONVERSATION_ID_HERE",
    "OriginatorConversationID": "29115-34620561-1",
    "ResultCode": 0,
    "ResultDesc": "The service request is processed successfully.",
    "ResultParameters": {
      "ResultParameter": [
        {"Key": "TransactionReceipt", "Value": "TEST_B2C_12345"},
        {"Key": "TransactionAmount", "Value": 150}
      ]
    }
  }
}'
```

**Verify payout marked as paid:**
```sql
SELECT status, error_message FROM payouts WHERE mpesa_transaction_id = [YOUR_TRANSACTION_ID];
-- Should show: status='paid', error_message contains receipt number
```

### Simulate Failed B2C Callback

**Send POST request with failure:**
```bash
curl -X POST http://localhost/mjengo/public/api/mpesa/callback/b2c \
-H "Content-Type: application/json" \
-d '{
  "Result": {
    "ConversationID": "YOUR_CONVERSATION_ID_HERE",
    "OriginatorConversationID": "29115-34620561-1",
    "ResultCode": 1,
    "ResultDesc": "Insufficient balance"
  }
}'
```

**Verify wallet refunded:**
```sql
-- Check payout failed
SELECT status, error_message FROM payouts WHERE mpesa_transaction_id = [YOUR_TRANSACTION_ID];
-- Should show: status='failed'

-- Check wallet credited back (refund)
SELECT * FROM wallet_transactions WHERE type='credit' AND reference_type='refund' ORDER BY created_at DESC LIMIT 1;
-- Should show refund with description
```

---

## Test #6: Full End-to-End Flow

1. **Top up wallet** (KES 500)
2. **Create 2 payouts** (KES 200 and KES 150)
3. **Approve first payout** → Wallet: KES 300 remaining
4. **Approve second payout** → Wallet: KES 150 remaining
5. **Simulate both B2C callbacks as successful**
6. **Verify both workers marked as paid**

**Final State:**
```sql
-- Owner wallet should be: 500 - 200 - 150 = 150
SELECT balance FROM owner_wallets WHERE user_id = [OWNER_ID];

-- Should have 4 wallet transactions:
-- 1 credit (top-up 500)
-- 2 debits (payouts 200, 150)
SELECT * FROM wallet_transactions ORDER BY created_at DESC;

-- Should have 3 M-Pesa transactions:
-- 1 STK Push (completed)
-- 2 B2C (completed)
SELECT transaction_type, status, amount FROM mpesa_transactions ORDER BY created_at DESC;
```

---

## Common Issues & Solutions

### Issue: "Failed to get access token"
**Solution:** Check your `MPESA_CONSUMER_KEY` and `MPESA_CONSUMER_SECRET` in `.env`

### Issue: Callback not received
**Solution:** 
- Use ngrok for local testing
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Manually simulate callback (see above)

### Issue: "Insufficient wallet balance" even though balance is enough
**Solution:** 
- Refresh wallet from DB: `$wallet->refresh();`
- Check for pending debits

### Issue: Wallet balance incorrect
**Solution:** 
- Check all wallet_transactions: `SELECT SUM(CASE WHEN type='credit' THEN amount ELSE -amount END) FROM wallet_transactions WHERE wallet_id = X;`
- Should match owner_wallets.balance

---

## Monitoring & Logs

**Monitor all M-Pesa activity:**
```bash
# Watch Laravel logs
tail -f storage/logs/laravel.log | grep -i mpesa

# Check recent M-Pesa transactions
mysql -u root sitegrid -e "SELECT id, transaction_type, status, amount, result_description, created_at FROM mpesa_transactions ORDER BY created_at DESC LIMIT 10;"

# Check wallet transaction history
mysql -u root sitegrid -e "SELECT id, type, amount, balance_after, description, created_at FROM wallet_transactions ORDER BY created_at DESC LIMIT 10;"
```

---

## Ready for Production?

Before switching to production (`MPESA_ENV=production`):

1. ✅ All sandbox tests passing
2. ✅ Callbacks working via ngrok
3. ✅ Wallet balance calculations correct
4. ✅ Refund logic tested
5. ✅ Production credentials obtained from Safaricom
6. ✅ Production callback URLs registered with Daraja
7. ✅ B2C security credential generated for production
8. ✅ Start with small test amounts in production

---

**Happy Testing! 🚀**
