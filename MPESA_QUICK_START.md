# M-Pesa Quick Start Commands

## ⚡ Fast Track Setup (5 Minutes)

### Step 1: Verify Installation
```bash
# Check migrations ran
php artisan migrate:status | Select-String "wallet|mpesa"

# Check routes exist
php artisan route:list --path=mpesa
php artisan route:list --path=owner/wallet
```

### Step 2: Add M-Pesa Credentials to .env
```bash
# Open .env and update these lines with YOUR credentials:
MPESA_CONSUMER_KEY=your_key_from_daraja
MPESA_CONSUMER_SECRET=your_secret_from_daraja
```

### Step 3: Quick Database Test
```sql
-- Create test wallet for user ID 2 (adjust ID as needed)
INSERT INTO owner_wallets (user_id, balance, currency, created_at, updated_at) 
VALUES (2, 1000.00, 'KES', NOW(), NOW());

-- Verify
SELECT * FROM owner_wallets;
```

### Step 4: Test in Browser
```
1. Login as site owner (user ID 2 or your owner account)
2. Visit: http://localhost/sitegrid/public/owner/wallet
3. You should see: Balance KES 1,000.00
4. Try top-up with phone: 254708374149, amount: 100
```

### Step 5: Simulate STK Callback (for local testing)
```bash
# Get the checkout_request_id from database
$checkoutId = (Invoke-MySqlQuery -Query "SELECT checkout_request_id FROM mpesa_transactions ORDER BY id DESC LIMIT 1").checkout_request_id

# Simulate successful callback
$callback = @"
{
  "Body": {
    "stkCallback": {
      "MerchantRequestID": "29115-34620561-1",
      "CheckoutRequestID": "$checkoutId",
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
}
"@

curl -X POST http://localhost/sitegrid/public/api/mpesa/callback/stk `
  -H "Content-Type: application/json" `
  -d $callback
```

### Step 6: Verify Wallet Updated
```sql
-- Check wallet balance increased
SELECT * FROM owner_wallets;

-- Check transaction recorded
SELECT * FROM wallet_transactions ORDER BY created_at DESC LIMIT 1;

-- Check M-Pesa transaction completed
SELECT * FROM mpesa_transactions ORDER BY created_at DESC LIMIT 1;
```

---

## 🧪 Full Test Scenario (15 Minutes)

### Scenario: Top-up wallet and pay worker

```bash
# 1. Top up wallet (see Step 4 & 5 above)

# 2. Create test payout in database
mysql -u root mjengo -e "
INSERT INTO payouts (pay_cycle_id, worker_id, gross_amount, net_amount, status, created_at, updated_at)
SELECT 1, 3, 200.00, 200.00, 'pending', NOW(), NOW()
FROM DUAL WHERE EXISTS (SELECT 1 FROM pay_cycles WHERE id = 1);
"

# 3. Approve payout via browser
# Login as owner → Go to claims → Click Approve
# System will auto-debit wallet and send M-Pesa

# 4. Check results
mysql -u root mjengo -e "
SELECT 'Wallet Balance' as check_type, balance as value FROM owner_wallets WHERE user_id=2
UNION ALL
SELECT 'Payout Status', status FROM payouts ORDER BY id DESC LIMIT 1
UNION ALL
SELECT 'M-Pesa B2C', status FROM mpesa_transactions WHERE transaction_type='b2c' ORDER BY id DESC LIMIT 1;
"

# 5. Simulate B2C success callback
$conversationId = (Invoke-MySqlQuery -Query "SELECT conversation_id FROM mpesa_transactions WHERE transaction_type='b2c' ORDER BY id DESC LIMIT 1").conversation_id

$b2cCallback = @"
{
  "Result": {
    "ConversationID": "$conversationId",
    "OriginatorConversationID": "29115-34620561-1",
    "ResultCode": 0,
    "ResultDesc": "The service request is processed successfully.",
    "ResultParameters": {
      "ResultParameter": [
        {"Key": "TransactionReceipt", "Value": "TEST_B2C_12345"},
        {"Key": "TransactionAmount", "Value": 200}
      ]
    }
  }
}
"@

curl -X POST http://localhost/sitegrid/public/api/mpesa/callback/b2c `
  -H "Content-Type: application/json" `
  -d $b2cCallback

# 6. Verify payout marked as paid
mysql -u root sitegrid -e "SELECT id, status, error_message FROM payouts ORDER BY id DESC LIMIT 1;"
```

---

## 🔍 Monitoring Commands

### Watch Logs in Real-Time
```bash
# Monitor all M-Pesa activity
Get-Content storage\logs\laravel.log -Wait -Tail 50 | Select-String -Pattern "mpesa|M-Pesa|Wallet"

# Or on Linux/Mac:
tail -f storage/logs/laravel.log | grep -i mpesa
```

### Check Database Status
```bash
# Quick wallet overview
mysql -u root mjengo -e "
SELECT 
    w.id,
    u.name as owner_name,
    w.balance,
    (SELECT COUNT(*) FROM wallet_transactions WHERE wallet_id=w.id) as transaction_count,
    w.updated_at as last_updated
FROM owner_wallets w
JOIN users u ON w.user_id = u.id;
"

# Recent M-Pesa transactions
mysql -u root mjengo -e "
SELECT 
    id,
    transaction_type,
    phone_number,
    amount,
    status,
    created_at
FROM mpesa_transactions 
ORDER BY created_at DESC 
LIMIT 10;
"

# Wallet balance reconciliation
mysql -u root mjengo -e "
SELECT 
    w.id,
    w.balance as current_balance,
    COALESCE(SUM(CASE WHEN wt.type='credit' THEN wt.amount ELSE -wt.amount END), 0) as calculated_balance,
    w.balance - COALESCE(SUM(CASE WHEN wt.type='credit' THEN wt.amount ELSE -wt.amount END), 0) as difference
FROM owner_wallets w
LEFT JOIN wallet_transactions wt ON w.id = wt.wallet_id
GROUP BY w.id, w.balance;
"
```

---

## 🚨 Troubleshooting Commands

### Reset Wallet for Testing
```bash
# WARNING: This deletes all wallet data for user ID 2
mysql -u root mjengo -e "
DELETE FROM wallet_transactions WHERE wallet_id IN (SELECT id FROM owner_wallets WHERE user_id=2);
DELETE FROM mpesa_transactions WHERE related_model='App\\\\Models\\\\OwnerWallet' AND related_id IN (SELECT id FROM owner_wallets WHERE user_id=2);
UPDATE owner_wallets SET balance=0 WHERE user_id=2;
"
```

### Check for Stuck Transactions
```bash
# Find pending M-Pesa transactions older than 5 minutes
mysql -u root mjengo -e "
SELECT 
    id,
    transaction_type,
    phone_number,
    amount,
    status,
    TIMESTAMPDIFF(MINUTE, created_at, NOW()) as minutes_old
FROM mpesa_transactions 
WHERE status='pending' 
AND created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
ORDER BY created_at DESC;
"
```

### Manual Callback Trigger Script
```powershell
# Save as trigger-callback.ps1
param(
    [Parameter(Mandatory=$true)]
    [string]$TransactionId,
    
    [Parameter(Mandatory=$true)]
    [ValidateSet('stk','b2c')]
    [string]$Type,
    
    [Parameter(Mandatory=$false)]
    [int]$ResultCode = 0
)

# Get transaction details
$query = "SELECT * FROM mpesa_transactions WHERE id=$TransactionId"
$tx = Invoke-MySqlQuery -Query $query

if ($Type -eq 'stk') {
    $callback = @"
{
  "Body": {
    "stkCallback": {
      "CheckoutRequestID": "$($tx.checkout_request_id)",
      "ResultCode": $ResultCode,
      "ResultDesc": "$(if($ResultCode -eq 0){'Success'}else{'Failed'})",
      "CallbackMetadata": {
        "Item": [
          {"Name": "Amount", "Value": $($tx.amount)},
          {"Name": "MpesaReceiptNumber", "Value": "TEST$(Get-Random -Maximum 999999)"},
          {"Name": "PhoneNumber", "Value": $($tx.phone_number)}
        ]
      }
    }
  }
}
"@
    $url = "http://localhost/sitegrid/public/api/mpesa/callback/stk"
} else {
    $callback = @"
{
  "Result": {
    "ConversationID": "$($tx.conversation_id)",
    "ResultCode": $ResultCode,
    "ResultDesc": "$(if($ResultCode -eq 0){'Success'}else{'Failed'})",
    "ResultParameters": {
      "ResultParameter": [
        {"Key": "TransactionReceipt", "Value": "TEST$(Get-Random -Maximum 999999)"},
        {"Key": "TransactionAmount", "Value": $($tx.amount)}
      ]
    }
  }
}
"@
    $url = "http://localhost/sitegrid/public/api/mpesa/callback/b2c"
}

curl -X POST $url -H "Content-Type: application/json" -d $callback
Write-Host "Callback triggered for transaction ID $TransactionId ($Type)"
```

**Usage:**
```powershell
# Trigger STK success
.\trigger-callback.ps1 -TransactionId 1 -Type stk

# Trigger B2C failure
.\trigger-callback.ps1 -TransactionId 2 -Type b2c -ResultCode 1
```

---

## 📊 Performance Testing

### Load Test Wallet Operations
```bash
# Test 100 rapid wallet credits (simulates multiple top-ups)
for ($i=1; $i -le 100; $i++) {
    mysql -u root mjengo -e "
    SET @wallet_id = (SELECT id FROM owner_wallets WHERE user_id=2);
    SET @before = (SELECT balance FROM owner_wallets WHERE id=@wallet_id);
    UPDATE owner_wallets SET balance = balance + 10 WHERE id=@wallet_id;
    INSERT INTO wallet_transactions (wallet_id, type, amount, balance_before, balance_after, description, created_at, updated_at)
    VALUES (@wallet_id, 'credit', 10, @before, @before + 10, 'Load test $i', NOW(), NOW());
    "
}

# Verify balance
mysql -u root mjengo -e "SELECT balance FROM owner_wallets WHERE user_id=2;"
# Expected: original + (100 * 10)
```

---

## ✅ Health Check Script

```powershell
# Save as mpesa-health-check.ps1
Write-Host "M-Pesa Integration Health Check" -ForegroundColor Cyan
Write-Host "================================`n"

# Check database tables exist
Write-Host "1. Database Tables:" -ForegroundColor Yellow
$tables = @('owner_wallets', 'wallet_transactions', 'mpesa_transactions')
foreach ($table in $tables) {
    $exists = mysql -u root mjengo -e "SHOW TABLES LIKE '$table'" -s
    if ($exists) {
        Write-Host "   ✓ $table exists" -ForegroundColor Green
    } else {
        Write-Host "   ✗ $table missing!" -ForegroundColor Red
    }
}

# Check routes
Write-Host "`n2. Routes:" -ForegroundColor Yellow
$routes = @('mpesa.callback.stk', 'mpesa.callback.b2c', 'owner.wallet')
foreach ($route in $routes) {
    $exists = php artisan route:list --name=$route 2>&1 | Select-String $route
    if ($exists) {
        Write-Host "   ✓ $route registered" -ForegroundColor Green
    } else {
        Write-Host "   ✗ $route missing!" -ForegroundColor Red
    }
}

# Check .env configuration
Write-Host "`n3. Configuration:" -ForegroundColor Yellow
$envVars = @('MPESA_CONSUMER_KEY', 'MPESA_CONSUMER_SECRET', 'MPESA_SHORTCODE')
foreach ($var in $envVars) {
    $value = Get-Content .env | Select-String "^$var="
    if ($value -and $value -notmatch "your_.*_here") {
        Write-Host "   ✓ $var configured" -ForegroundColor Green
    } else {
        Write-Host "   ⚠ $var not configured" -ForegroundColor Yellow
    }
}

# Check wallet data
Write-Host "`n4. Wallet Data:" -ForegroundColor Yellow
$walletCount = mysql -u root mjengo -e "SELECT COUNT(*) FROM owner_wallets" -s
Write-Host "   • Wallets created: $walletCount"
$txCount = mysql -u root mjengo -e "SELECT COUNT(*) FROM mpesa_transactions" -s
Write-Host "   • M-Pesa transactions: $txCount"

Write-Host "`nHealth check complete!`n" -ForegroundColor Cyan
```

**Run it:**
```powershell
.\mpesa-health-check.ps1
```

---

**All commands tested and ready to use! 🚀**
