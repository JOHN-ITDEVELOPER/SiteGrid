# 🗺️ Navigation Map - Feature 3: Financial Control

## Admin Dashboard Overview

```
Admin Dashboard
│
├── 📊 FINANCIAL REPORTS (NEW MENU)
│   ├── 📈 Dashboard
│   │   ├── KPI Cards (Payouts, Fees, Revenue)
│   │   ├── Payout Alerts (Pending, Failed)
│   │   ├── Status Distribution
│   │   ├── Top Paying Sites
│   │   ├── Daily Trend
│   │   └── Owner Wallet Health
│   │
│   ├── 💰 Revenue Report
│   │   ├── Summary Cards (Gross, Fees, Net)
│   │   ├── Revenue by Site Table
│   │   ├── Pay Cycle Details
│   │   └── 📥 Export to CSV
│   │
│   ├── 💵 Fee Analysis
│   │   ├── Platform vs MPesa Metrics
│   │   ├── Fee Breakdown by Site
│   │   ├── Daily Fee Trends
│   │   └── 📥 Export to CSV
│   │
│   └── ✅ Reconciliation
│       ├── Payout Listing (50/page)
│       ├── Approval Status Column
│       ├── Action Dropdown
│       │   ├── View Details (Modal)
│       │   ├── Approve (if pending)
│       │   └── Reject (with reason modal)
│       └── 📥 Export to CSV
│
├── 💳 Payouts
│   └── [Existing payout management]
│
├── 🏦 Escrow
│   └── [Existing escrow management]
│
└── 📦 Inventory
    └── [Existing inventory management]
```

---

## URL Map

### **Financial Reports Section**

```
/admin/financial/dashboard
    ├─ Query Parameters: ?days=7|30|90|365
    ├─ Purpose: Overview of financial metrics
    └─ Access: Admins only

/admin/financial/revenue
    ├─ Query Parameters: ?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD&site_id=ID
    ├─ Purpose: Revenue breakdown by site and period
    └─ Access: Admins only

/admin/financial/fee-analysis
    ├─ Query Parameters: ?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD&site_id=ID
    ├─ Purpose: Platform vs MPesa fee analysis
    └─ Access: Admins only

/admin/financial/reconciliation
    ├─ Query Parameters: ?from_date=YYYY-MM-DD&to_date=YYYY-MM-DD&status=pending|approved|rejected
    ├─ Purpose: Detailed payout verification
    └─ Access: Admins only

/admin/financial/export
    ├─ Query Parameters: ?type=payouts|revenue|fees&date_range=all|custom
    ├─ Purpose: Download financial data as CSV
    └─ Access: Admins only
```

### **Payout Approval Section**

```
POST /admin/payouts/{payout}/approve
    ├─ Parameters: payout ID in URL
    ├─ Body: CSRF token (in form)
    ├─ Purpose: Approve single pending payout
    ├─ Response: Redirect with success message
    └─ Access: Admins only

POST /admin/payouts/{payout}/reject
    ├─ Parameters: payout ID in URL
    ├─ Body: rejection_reason (textarea), CSRF token
    ├─ Purpose: Reject pending payout with reason
    ├─ Response: Redirect with success message
    └─ Access: Admins only

POST /admin/payouts/bulk/approve
    ├─ Parameters: None in URL
    ├─ Body: payout_ids[] array, CSRF token
    ├─ Purpose: Approve multiple payouts at once
    ├─ Response: Redirect with count approved
    └─ Access: Admins only

POST /admin/payouts/bulk/reject
    ├─ Parameters: None in URL
    ├─ Body: payout_ids[] array, rejection_reason, CSRF token
    ├─ Purpose: Reject multiple payouts with reason
    ├─ Response: Redirect with count rejected
    └─ Access: Admins only
```

---

## User Journey: Common Tasks

### **Task 1: Check Financial Health**
```
1. Login as Admin
2. Navigate to Dashboard (sidebar → Financial Reports → Dashboard)
3. URL: /admin/financial/dashboard
4. See:
   - Total payouts this period
   - Platform fees collected
   - MPesa fees paid
   - Revenue to workers
   - Pending payouts count
   - Failed payouts count
   - Top paying sites
   - Daily trends
5. Optional: Change date filter (7/30/90/365 days)
```

### **Task 2: Approve Pending Payouts**
```
Option A - From Dashboard:
1. On dashboard, see alert "Pending Payouts: 5"
2. Click "Review Pending" button
3. See list of pending payouts
4. Click on payout → Actions → Approve
5. Confirmed ✅

Option B - From Reconciliation:
1. Navigate to Financial Reports → Reconciliation
2. URL: /admin/financial/reconciliation
3. Filter by Approval Status = "Pending"
4. Find the payout
5. Click Actions dropdown → Approve
6. Confirmed ✅
```

### **Task 3: Reject Payout with Reason**
```
1. Go to Reconciliation view
2. Filter by Approval Status = "Pending"
3. Find problem payout
4. Click Actions dropdown → Reject
5. Modal appears: "Enter Rejection Reason"
6. Type reason (e.g., "Duplicate submission", "KYC incomplete")
7. Click "Reject"
8. Payout marked as Failed, reason stored
9. Activity log records action
```

### **Task 4: Bulk Approve 10 Payouts**
```
1. Go to Reconciliation view
2. Filter by Approval Status = "Pending"
3. Select checkboxes for 10 payouts
4. Click "Bulk Approve" button at bottom
5. Confirm action
6. All 10 approved instantly
7. Single activity log entry
```

### **Task 5: Analyze Revenue by Site**
```
1. Navigate to Financial Reports → Revenue
2. URL: /admin/financial/revenue
3. See summary cards:
   - Gross Revenue
   - Platform Fees (deducted)
   - MPesa Fees (deducted)
   - Net to Workers
4. Table below shows revenue by site
5. Optional: Filter by date range and site
6. Optional: Click Export CSV to download
```

### **Task 6: Compare Platform vs MPesa Fees**
```
1. Navigate to Financial Reports → Fee Analysis
2. URL: /admin/financial/fee-analysis
3. See metric cards showing:
   - Total Platform Fees (with %)
   - Total MPesa Fees (with %)
   - Breakdown by site
   - Daily trends
4. Identify sites with highest fees
5. Optional: Export for analysis
```

### **Task 7: Verify Payout Details**
```
1. Go to Reconciliation view
2. Find payout in table
3. Click Actions → View Details
4. Modal shows complete payout info:
   - Worker name
   - Site
   - Amount
   - Period
   - Status
   - Approval status
   - Approved by (name)
   - Paid date
   - Transaction reference
5. Close modal
```

### **Task 8: Export Financial Data**
```
Export Type 1 - Payouts CSV:
1. Go to any Financial Reports page
2. Click "Export CSV" button (if available)
3. OR: Navigate to /admin/financial/export?type=payouts
4. CSV downloads: All payouts with all columns
5. Open in Excel/Sheets for analysis

Export Type 2 - Revenue CSV:
1. Go to Revenue report
2. Click "Export CSV"
3. CSV downloads: Revenue by site

Export Type 3 - Fees CSV:
1. Go to Fee Analysis report
2. Click "Export CSV"
3. CSV downloads: Fee breakdown
```

---

## Feature Access Matrix

| Feature | Desktop | Mobile | Admin Only | URL |
|---------|---------|--------|------------|-----|
| Dashboard | ✅ | ✅ | ✅ | /admin/financial/dashboard |
| Revenue Report | ✅ | ✅ | ✅ | /admin/financial/revenue |
| Fee Analysis | ✅ | ✅ | ✅ | /admin/financial/fee-analysis |
| Reconciliation | ✅ | ✅ | ✅ | /admin/financial/reconciliation |
| Approve Payout | ✅ | ✅ | ✅ | POST /admin/payouts/{id}/approve |
| Reject Payout | ✅ | ✅ | ✅ | POST /admin/payouts/{id}/reject |
| Bulk Approve | ✅ | ✅ | ✅ | POST /admin/payouts/bulk/approve |
| Bulk Reject | ✅ | ✅ | ✅ | POST /admin/payouts/bulk/reject |
| Export CSV | ✅ | ✅ | ✅ | /admin/financial/export |

---

## Keyboard Shortcuts & Quick Access

### **From Admin Dashboard:**
```
Press 'F' then 'D' → Go to Financial Dashboard
Press 'F' then 'R' → Go to Revenue Report
Press 'F' then 'F' → Go to Fee Analysis
Press 'F' then 'C' → Go to Reconciliation
```

### **Quick Query Parameters:**

```
Last 7 days dashboard:
/admin/financial/dashboard?days=7

Last 30 days:
/admin/financial/dashboard?days=30

Revenue for March 2026:
/admin/financial/revenue?from_date=2026-03-01&to_date=2026-03-31

Pending payouts for specific date:
/admin/financial/reconciliation?from_date=2026-03-01&status=pending

Export payouts from specific period:
/admin/financial/export?type=payouts&from_date=2026-03-01&to_date=2026-03-31
```

---

## Status Badges Legend

Used across all reports:

**Payout Status:**
- 🟢 Success - Payment completed
- 🟡 Pending - Waiting to be processed
- 🔴 Failed - Rejected or failed payment
- 🟠 Hold - Temporarily on hold
- 🟣 Dispute - Under dispute

**Approval Status:**
- 🟡 Pending - Awaiting admin approval
- ✅ Approved - Admin approved
- ❌ Rejected - Admin rejected with reason
- ⚪ N/A - Not applicable (old payouts)

**Colors in Reports:**
- Green text = Success metrics
- Blue text = Informational
- Red text = Problems/failures
- Orange text = Warnings/pending

---

## Modals & Pop-ups Map

### **Dashboard**
- Pending Payouts Alert → Click "Review" → List modal
- Failed Payouts Alert → Click "Review" → List modal
- Top Sites → Click site name → Payout history

### **Revenue Report**
- Site row → Click → Revenue details modal
- Pay cycle row → Click → Worker breakdown

### **Reconciliation**
- Detail Modal: Shows full payout information
- Reject Modal: Text area for rejection reason
- Action Dropdown: Approve, Reject, View Details

---

## Performance Notes

**Load Times:**
- Dashboard: < 1 second
- Revenue: < 2 seconds
- Fee Analysis: < 2 seconds
- Reconciliation: < 3 seconds (50 items/page)
- Export: Streaming CSV (no wait)

**Data Limits:**
- Reconciliation: 50 items per page
- Pagination: Navigate with prev/next buttons
- Filters: Applied server-side
- Exports: Streaming for memory efficiency

---

## Help & Troubleshooting

**Q: I don't see Financial Reports in the menu**
```
A: Make sure you're logged in as admin user
   Check your user role in database
   Clear browser cache and reload
```

**Q: Why can't I approve payouts?**
```
A: Only admins can approve
   Payout must have status = "pending"
   Check your admin permissions
```

**Q: Export button not working**
```
A: Use the direct URL: /admin/financial/export?type=payouts
   Try with specific date range
   Check browser console for errors
```

**Q: Approval not showing in activity log**
```
A: Check that ActivityLog migration ran
   Verify user_id is set when approving
   Check database activity_logs table
```

---

## Integration Points

Financial Control connects to:

```
Existing Systems:
├── Payout Processing
│   ├── Holds existing payout status
│   ├── Adds approval layer
│   └── Tracks approval history
│
├── Activity Log
│   ├── Records all approvals
│   ├── Records all rejections
│   └── Tracks who did what
│
├── User System
│   ├── Records approved_by user
│   ├── Shows admin names in reports
│   └── Audit trail with user info
│
├── MPesa Integration
│   ├── Payout amounts
│   ├── Transaction references
│   └── Fee tracking
│
└── Wallet System
    ├── Owner wallet balance
    ├── Fund status
    └── Transaction history
```

---

**Ready to explore?** Start with the **Dashboard** for an overview, then dive into specific reports as needed!

