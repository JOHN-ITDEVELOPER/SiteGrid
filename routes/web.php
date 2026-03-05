<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AuditLogsController;
use App\Http\Controllers\Admin\AttendanceAuditController;
use App\Http\Controllers\Admin\PaycyclesController;
use App\Http\Controllers\Admin\WebhookLogsController;
use App\Http\Controllers\Admin\IntegrationHealthController;
use App\Http\Controllers\Admin\ActivityFeedController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\EscrowController;
use App\Http\Controllers\Admin\PayoutOverrideController;
use App\Http\Controllers\Admin\FinancialReportsController;
use App\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use App\Http\Controllers\Foreman\DashboardController as ForemanDashboardController;
use App\Http\Controllers\Foreman\InventoryController as ForemanInventoryController;
use App\Http\Controllers\Owner\AccountSettingsController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\InventoryController as OwnerInventoryController;
use App\Http\Controllers\Owner\InventoryCategoryController;
use App\Http\Controllers\Owner\InventoryItemController;
use App\Http\Controllers\Owner\SiteSettingsController;
use App\Http\Controllers\Worker\DashboardController as WorkerDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Landing page
Route::get('/', [LandingController::class, 'index'])->name('landing');

// Signup flow
Route::post('/signup/phone', [LandingController::class, 'submitPhone'])->name('signup.phone');
Route::post('/signup/verify-otp', [LandingController::class, 'verifyOtp'])->name('signup.verify-otp');
Route::post('/signup/resend-otp', [LandingController::class, 'resendOtp'])->name('signup.resend-otp');

// Demo request
Route::post('/demo', [LandingController::class, 'submitDemo'])->name('demo.submit');

// Registration routes (new dedicated page)
Route::get('/register', [\App\Http\Controllers\RegisterController::class, 'show'])->name('register');
Route::post('/register/email', [\App\Http\Controllers\RegisterController::class, 'registerEmail'])->middleware('throttle:5,1')->name('register.email');

// Email verification routes
Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\RegisterController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('/email/resend', [\App\Http\Controllers\RegisterController::class, 'resendVerificationEmail'])
    ->middleware('throttle:3,1')
    ->name('verification.send');

// Login routes (password-based) with rate limiting
Route::get('/login', [\App\Http\Controllers\LoginController::class, 'showLoginPage'])->name('login');
Route::post('/login', [\App\Http\Controllers\LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
Route::post('/logout', [\App\Http\Controllers\LoginController::class, 'logout'])->name('logout');

// Profile completion routes
Route::middleware(['auth', 'verified:profile'])->group(function () {
    Route::get('/profile/complete', [\App\Http\Controllers\ProfileController::class, 'complete'])->name('profile.complete');
    Route::post('/profile/complete', [\App\Http\Controllers\ProfileController::class, 'store'])->name('profile.store');
});

// Password reset routes with rate limiting
Route::get('/forgot-password', [\App\Http\Controllers\ForgotPasswordController::class, 'show'])->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\ForgotPasswordController::class, 'sendResetLink'])->middleware('throttle:3,1')->name('password.email');
Route::post('/forgot-password/phone', [\App\Http\Controllers\ForgotPasswordController::class, 'sendPhoneOtp'])->middleware('throttle:3,1')->name('password.phone');
Route::get('/reset-password/{token}', [\App\Http\Controllers\ForgotPasswordController::class, 'show'])->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\ForgotPasswordController::class, 'reset'])->middleware('throttle:3,1')->name('password.update');

Route::get('/invite/{token}', [SiteSettingsController::class, 'acceptInvitationForm'])->name('invites.accept.form');
Route::post('/invite/{token}/accept', [SiteSettingsController::class, 'acceptInvitation'])->name('invites.accept.submit');

// Dashboard (protected)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard.index');
    })->name('dashboard');
});

// Admin Dashboard
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Site routes - create and edit must come before resource
    Route::get('/sites/create', [DashboardController::class, 'createSite'])->name('sites.create');
    Route::get('/sites/{site}/edit', [DashboardController::class, 'editSite'])->name('sites.edit');
    Route::get('/sites', [DashboardController::class, 'sites'])->name('sites.index');
    Route::post('/sites', [DashboardController::class, 'storeSite'])->name('sites.store');
    Route::get('/sites/{site}', [DashboardController::class, 'siteShow'])->name('sites.show');
    Route::put('/sites/{site}', [DashboardController::class, 'updateSite'])->name('sites.update');
    Route::delete('/sites/{site}', [DashboardController::class, 'deleteSite'])->name('sites.delete');
    
    // Site Policy routes
    Route::get('/sites/{site}/policy', [DashboardController::class, 'editSitePolicy'])->name('sites.policy.edit');
    Route::put('/sites/{site}/policy', [DashboardController::class, 'updateSitePolicy'])->name('sites.policy.update');
    Route::post('/sites/{site}/lockdown', [DashboardController::class, 'lockdownSite'])->name('sites.lockdown');
    Route::post('/sites/{site}/unlock', [DashboardController::class, 'unlockdownSite'])->name('sites.unlock');
    
    Route::get('/payouts', [DashboardController::class, 'payouts'])->name('payouts.index');
    Route::get('/invoices', [DashboardController::class, 'invoices'])->name('invoices.index');
    
    // Worker routes - specific routes must come before resource
    Route::get('/workers', [\App\Http\Controllers\Admin\WorkersController::class, 'index'])->name('workers.index');
    Route::get('/workers/create', [\App\Http\Controllers\Admin\WorkersController::class, 'create'])->name('workers.create');
    Route::post('/workers', [\App\Http\Controllers\Admin\WorkersController::class, 'store'])->name('workers.store');
    Route::get('/workers/{worker}/edit', [\App\Http\Controllers\Admin\WorkersController::class, 'edit'])->name('workers.edit');
    Route::put('/workers/{worker}', [\App\Http\Controllers\Admin\WorkersController::class, 'update'])->name('workers.update');
    Route::post('/workers/{worker}/deactivate', [\App\Http\Controllers\Admin\WorkersController::class, 'deactivate'])->name('workers.deactivate');
    Route::post('/workers/{worker}/reactivate', [\App\Http\Controllers\Admin\WorkersController::class, 'reactivate'])->name('workers.reactivate');
    Route::get('/workers/user/{user}/history', [\App\Http\Controllers\Admin\WorkersController::class, 'history'])->name('workers.history');
    Route::post('/workers-bulk', [\App\Http\Controllers\Admin\WorkersController::class, 'bulkAction'])->name('workers.bulk');
    
    // User routes - specific routes must come before resource
    Route::get('/users/create', [UsersController::class, 'create'])->name('users.create');
    Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
    Route::resource('/users', UsersController::class)->except(['create', 'edit']);
    Route::post('/users/{user}/sites', [UsersController::class, 'storeSiteForOwner'])->name('users.sites.store');
    Route::post('/users/{user}/suspend', [UsersController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/reactivate', [UsersController::class, 'reactivateUser'])->name('users.reactivate');
    Route::post('/users/{user}/force-password-reset', [UsersController::class, 'forcePasswordReset'])->name('users.force-password-reset');
    Route::get('/users/{user}/activity', [UsersController::class, 'activity'])->name('users.activity');
    Route::get('/users-export', [UsersController::class, 'export'])->name('users.export');
    Route::post('/users-bulk', [UsersController::class, 'bulkAction'])->name('users.bulk');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-mpesa', [SettingsController::class, 'testMpesa'])->name('settings.test-mpesa');
    Route::post('/settings/preview-invoice', [SettingsController::class, 'previewInvoice'])->name('settings.preview-invoice');
    Route::post('/settings/trigger-backup', [SettingsController::class, 'triggerBackup'])->name('settings.trigger-backup');
    Route::post('/settings/simulate-ussd', [SettingsController::class, 'simulateUssd'])->name('settings.simulate-ussd');

    // Payment Accounts (new - for deposit/invoice/payout routing)
    Route::get('/accounts', [SettingsController::class, 'accounts'])->name('accounts.index');
    Route::post('/accounts/save', [SettingsController::class, 'saveAccount'])->name('accounts.save');
    Route::post('/accounts/{id}/test', [SettingsController::class, 'testAccount'])->name('accounts.test');

    Route::get('/audit', [AuditLogsController::class, 'index'])->name('audit.index');
    Route::get('/kyc/pending', [DashboardController::class, 'kycPending'])->name('kyc.pending');
    Route::post('/kyc/{user}/approve', [DashboardController::class, 'approveKyc'])->name('kyc.approve');
    Route::post('/kyc/{user}/reject', [DashboardController::class, 'rejectKyc'])->name('kyc.reject');
    
    // Attendance Audit
    Route::get('/attendance', [AttendanceAuditController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{attendance}', [AttendanceAuditController::class, 'show'])->name('attendance.show');
    Route::post('/attendance/{attendance}/correct', [AttendanceAuditController::class, 'correct'])->name('attendance.correct');
    
    // Paycycles
    Route::get('/paycycles', [PaycyclesController::class, 'index'])->name('paycycles.index');
    Route::get('/paycycles/{paycycle}', [PaycyclesController::class, 'show'])->name('paycycles.show');
    Route::post('/paycycles/{paycycle}/recalculate', [PaycyclesController::class, 'recalculate'])->name('paycycles.recalculate');
    Route::post('/payouts/{payout}/retry', [PaycyclesController::class, 'retryPayout'])->name('payouts.retry');
    
    // Webhook Logs
    Route::get('/webhooks', [WebhookLogsController::class, 'index'])->name('webhooks.index');
    Route::get('/webhooks/{log}', [WebhookLogsController::class, 'show'])->name('webhooks.show');
    Route::post('/webhooks/{log}/retry', [WebhookLogsController::class, 'retry'])->name('webhooks.retry');
    
    // Integration Health
    Route::get('/integration-health', [IntegrationHealthController::class, 'index'])->name('integration-health.index');
    
    // Activity Feed
    Route::get('/activity', [ActivityFeedController::class, 'index'])->name('activity.index');
    Route::get('/activity/latest', [ActivityFeedController::class, 'latest'])->name('activity.latest');
    
    // Impersonation
    Route::post('/users/{user}/impersonate', [ImpersonationController::class, 'impersonate'])->name('users.impersonate');
    Route::post('/impersonate/leave', [ImpersonationController::class, 'leave'])->name('impersonate.leave');
    
    // Global Search
    Route::get('/search', [GlobalSearchController::class, 'search'])->name('search');
    
    // Escrow
    Route::get('/escrow', [EscrowController::class, 'index'])->name('escrow.index');
    Route::post('/payouts/{payout}/hold', [EscrowController::class, 'hold'])->name('payouts.hold');
    Route::post('/payouts/{payout}/release', [EscrowController::class, 'release'])->name('payouts.release');
    Route::post('/payouts/{payout}/dispute', [EscrowController::class, 'dispute'])->name('payouts.dispute');

    // Payout Override/Approval
    Route::post('/payouts/{payout}/approve', [PayoutOverrideController::class, 'approve'])->name('payouts.approve');
    Route::post('/payouts/{payout}/reject', [PayoutOverrideController::class, 'reject'])->name('payouts.reject');
    Route::post('/payouts/bulk/approve', [PayoutOverrideController::class, 'bulkApprove'])->name('payouts.bulk-approve');
    Route::post('/payouts/bulk/reject', [PayoutOverrideController::class, 'bulkReject'])->name('payouts.bulk-reject');

    // Financial Reports
    Route::get('/financial/dashboard', [FinancialReportsController::class, 'dashboard'])->name('financial.dashboard');
    Route::get('/financial/revenue', [FinancialReportsController::class, 'revenue'])->name('financial.revenue');
    Route::get('/financial/platform-revenue', [FinancialReportsController::class, 'platformRevenue'])->name('financial.platform-revenue');
    Route::get('/financial/fee-analysis', [FinancialReportsController::class, 'feeAnalysis'])->name('financial.fee-analysis');
    Route::get('/financial/reconciliation', [FinancialReportsController::class, 'reconciliation'])->name('financial.reconciliation');
    Route::get('/financial/export', [FinancialReportsController::class, 'export'])->name('financial.export');

    // Inventory Command Center
    Route::get('/inventory', [AdminInventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/progress/{progressLog}', [AdminInventoryController::class, 'showProgress'])->name('inventory.progress.show');
    Route::post('/inventory/requests/{procurementRequest}/approve', [AdminInventoryController::class, 'approveProcurement'])->name('inventory.requests.approve');
    Route::post('/inventory/requests/{procurementRequest}/reject', [AdminInventoryController::class, 'rejectProcurement'])->name('inventory.requests.reject');
    Route::delete('/inventory/requests/{procurementRequest}', [AdminInventoryController::class, 'deleteProcurement'])->name('inventory.requests.destroy');
});

// Site Owner Dashboard
Route::middleware(['auth', 'owner'])->prefix('owner')->name('owner.')->group(function () {
    // Dashboard & Views
    Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/account/settings', [AccountSettingsController::class, 'edit'])->name('account.settings');
    Route::get('/sites', [OwnerDashboardController::class, 'sites'])->name('sites');
    Route::post('/sites', [OwnerDashboardController::class, 'storeSite'])->name('sites.store');
    Route::get('/sites/{site}', [OwnerDashboardController::class, 'siteDetail'])->name('sites.detail');
    Route::get('/workforce', [OwnerDashboardController::class, 'workforce'])->name('workforce');
    Route::get('/payroll', [OwnerDashboardController::class, 'payroll'])->name('payroll');
    Route::get('/invoices', [OwnerDashboardController::class, 'invoices'])->name('invoices');
    Route::get('/disputes', [OwnerDashboardController::class, 'disputes'])->name('disputes');
    
    // Existing Actions
    Route::post('/paycycles/{paycycle}/approve', [OwnerDashboardController::class, 'approvePaycycle'])->name('paycycles.approve');
    Route::post('/payouts/{payout}/acknowledge-dispute', [OwnerDashboardController::class, 'acknowledgeDispute'])->name('payouts.acknowledge-dispute');
    Route::post('/invoices/{invoice}/retry-payment', [OwnerDashboardController::class, 'retryInvoicePayment'])->name('invoices.retry-payment');
    
    // Worker Management
    Route::get('/workers/add', [OwnerDashboardController::class, 'addWorker'])->name('workers.add');
    Route::post('/workers', [OwnerDashboardController::class, 'storeWorker'])->name('workers.store');
    Route::get('/workers/{worker}/edit', [OwnerDashboardController::class, 'editWorker'])->name('workers.edit');
    Route::put('/workers/{worker}', [OwnerDashboardController::class, 'updateWorker'])->name('workers.update');
    Route::post('/workers/{worker}/deactivate', [OwnerDashboardController::class, 'deactivateWorker'])->name('workers.deactivate');
    Route::post('/workers/{worker}/reactivate', [OwnerDashboardController::class, 'reactivateWorker'])->name('workers.reactivate');
    
    // Attendance Management
    Route::get('/attendance', [OwnerDashboardController::class, 'attendance'])->name('attendance');
    Route::post('/attendance/mark', [OwnerDashboardController::class, 'markAttendance'])->name('attendance.mark');
    Route::post('/attendance/bulk-mark', [OwnerDashboardController::class, 'bulkMarkAttendance'])->name('attendance.bulk-mark');
    
    // Pay-Cycle Management
    Route::get('/paycycles/create', [OwnerDashboardController::class, 'createPaycycle'])->name('paycycles.create');
    Route::post('/paycycles', [OwnerDashboardController::class, 'storePaycycle'])->name('paycycles.store');
    
    // Wallet & Billing
    Route::get('/wallet', [OwnerDashboardController::class, 'wallet'])->name('wallet');
    Route::post('/wallet/topup', [OwnerDashboardController::class, 'initiateTopup'])->name('wallet.topup');
    Route::get('/wallet/transaction/{checkoutRequestId}/status', [OwnerDashboardController::class, 'checkTransactionStatus'])->name('wallet.transaction.status');
    
    // Claims Center
    Route::get('/claims', [OwnerDashboardController::class, 'claims'])->name('claims');
    Route::post('/claims/{claim}/action', [OwnerDashboardController::class, 'approveClaim'])->name('claims.action');

    // Inventory & Procurement
    Route::get('/inventory', [OwnerInventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/progress/{progressLog}', [OwnerInventoryController::class, 'showProgress'])->name('inventory.progress.show');
    Route::post('/inventory/progress/{progressLog}/status', [OwnerInventoryController::class, 'updateProgressStatus'])->name('inventory.progress.update-status');
    Route::post('/inventory/requests/{procurementRequest}/approve', [OwnerInventoryController::class, 'approve'])->name('inventory.requests.approve');
    Route::post('/inventory/requests/{procurementRequest}/reject', [OwnerInventoryController::class, 'reject'])->name('inventory.requests.reject');
    Route::post('/inventory/requests/{procurementRequest}/receive', [OwnerInventoryController::class, 'receive'])->name('inventory.requests.receive');
    Route::post('/inventory/direct-stock-in', [OwnerInventoryController::class, 'directStockIn'])->name('inventory.direct-stock-in');
    Route::patch('/inventory/stocks/{inventoryStock}/threshold', [OwnerInventoryController::class, 'updateThreshold'])->name('inventory.update-threshold');
    Route::delete('/inventory/requests/{procurementRequest}', [OwnerInventoryController::class, 'deleteProcurement'])->name('inventory.requests.destroy');
    
    // Inventory Categories Management
    Route::get('/inventory/categories', [InventoryCategoryController::class, 'index'])->name('inventory.categories.index');
    Route::get('/inventory/categories/create', [InventoryCategoryController::class, 'create'])->name('inventory.categories.create');
    Route::post('/inventory/categories', [InventoryCategoryController::class, 'store'])->name('inventory.categories.store');
    Route::get('/inventory/categories/{category}/edit', [InventoryCategoryController::class, 'edit'])->name('inventory.categories.edit');
    Route::put('/inventory/categories/{category}', [InventoryCategoryController::class, 'update'])->name('inventory.categories.update');
    Route::delete('/inventory/categories/{category}', [InventoryCategoryController::class, 'destroy'])->name('inventory.categories.destroy');
    Route::post('/inventory/categories/apply-template', [InventoryCategoryController::class, 'applyTemplate'])->name('inventory.categories.apply-template');
    
    // Inventory Items Management
    Route::get('/inventory/categories/{category}/items', [InventoryItemController::class, 'index'])->name('inventory.items.index');
    Route::get('/inventory/categories/{category}/items/create', [InventoryItemController::class, 'create'])->name('inventory.items.create');
    Route::post('/inventory/categories/{category}/items', [InventoryItemController::class, 'store'])->name('inventory.items.store');
    Route::get('/inventory/categories/{category}/items/{item}/edit', [InventoryItemController::class, 'edit'])->name('inventory.items.edit');
    Route::put('/inventory/categories/{category}/items/{item}', [InventoryItemController::class, 'update'])->name('inventory.items.update');
    Route::delete('/inventory/categories/{category}/items/{item}', [InventoryItemController::class, 'destroy'])->name('inventory.items.destroy');
    
    // Window Override (for pending worker claims)
    Route::post('/claims/{claim}/override-window', [OwnerDashboardController::class, 'overrideWithdrawalWindow'])->name('claims.override-window');
    
    // Quick Actions
    Route::post('/sites/{site}/mark-completed', [OwnerDashboardController::class, 'markSiteCompleted'])->name('sites.mark-completed');

    // Site Settings
    Route::get('/sites/{site}/settings', [SiteSettingsController::class, 'edit'])->name('sites.settings');
    Route::put('/sites/{site}/settings/payouts', [SiteSettingsController::class, 'updatePayouts'])->name('sites.settings.payouts.update');
    Route::post('/sites/{site}/settings/payouts/test', [SiteSettingsController::class, 'testPayoutAccount'])->name('sites.settings.payouts.test');
    Route::put('/sites/{site}/settings/billing', [SiteSettingsController::class, 'updateBilling'])->name('sites.settings.billing.update');
    Route::put('/sites/{site}/settings/communications', [SiteSettingsController::class, 'updateCommunications'])->name('sites.settings.communications.update');
    Route::post('/sites/{site}/settings/communications/preview', [SiteSettingsController::class, 'previewTemplate'])->name('sites.settings.communications.preview');
    Route::post('/sites/{site}/settings/communications/test-sms', [SiteSettingsController::class, 'sendTestSms'])->name('sites.settings.communications.test-sms');
    Route::put('/sites/{site}/settings/features', [SiteSettingsController::class, 'updateFeatures'])->name('sites.settings.features.update');
    Route::put('/sites/{site}/settings/notifications', [SiteSettingsController::class, 'updateNotifications'])->name('sites.settings.notifications.update');
    Route::post('/sites/{site}/settings/invitations', [SiteSettingsController::class, 'storeInvitation'])->name('sites.settings.invitations.store');

    // Account Settings
    Route::put('/account/settings/profile', [AccountSettingsController::class, 'updateProfile'])->name('account.settings.profile.update');
    Route::put('/account/settings/security', [AccountSettingsController::class, 'updateSecurity'])->name('account.settings.security.update');
    Route::put('/account/settings/preferences', [AccountSettingsController::class, 'updatePreferences'])->name('account.settings.preferences.update');
    
    // Exports
    Route::get('/exports/payroll', [OwnerDashboardController::class, 'exportPayroll'])->name('exports.payroll');
    Route::get('/exports/attendance', [OwnerDashboardController::class, 'exportAttendance'])->name('exports.attendance');
});

// Field Staff (Workers & Foremen) - Protected with both have access
Route::middleware(['auth'])->prefix('field')->name('field.')->group(function () {
    // All staff (worker & foreman)
    Route::get('/dashboard', [WorkerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/claims', [WorkerDashboardController::class, 'claimsIndex'])->name('claims');
    Route::post('/claims', [WorkerDashboardController::class, 'storeClaim'])->name('claims.store');
    Route::get('/attendance', [WorkerDashboardController::class, 'attendanceIndex'])->name('attendance');
    Route::get('/payhistory', [WorkerDashboardController::class, 'payHistoryIndex'])->name('payhistory');
    Route::get('/settings', [WorkerDashboardController::class, 'settingsIndex'])->name('settings');
    Route::put('/settings', [WorkerDashboardController::class, 'updateSettings'])->name('settings.update');

    // Foreman only
    Route::middleware('foreman')->group(function () {
        Route::get('/roster', [ForemanDashboardController::class, 'rosterIndex'])->name('roster');
        Route::post('/attendance/mark', [ForemanDashboardController::class, 'markAttendance'])->name('attendance.mark');
        Route::post('/attendance/bulk-mark', [ForemanDashboardController::class, 'bulkMarkAttendance'])->name('attendance.bulk-mark');
        Route::post('/attendance/bulk', [ForemanDashboardController::class, 'bulkAttendance'])->name('attendance.bulk');
        Route::get('/claims-approval', [ForemanDashboardController::class, 'claimsApprovalIndex'])->name('claims-approval');
        Route::post('/claims/bulk-action', [ForemanDashboardController::class, 'bulkClaimAction'])->name('claims.bulk-action');
        Route::get('/add-worker', [ForemanDashboardController::class, 'addWorkerIndex'])->name('add-worker');
        Route::post('/add-worker', [ForemanDashboardController::class, 'storeWorker'])->name('add-worker.store');

        Route::get('/inventory', [ForemanInventoryController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/progress/{progressLog}', [ForemanInventoryController::class, 'showProgress'])->name('inventory.progress.show');
        Route::post('/inventory/requests', [ForemanInventoryController::class, 'storeRequest'])->name('inventory.requests.store');
        Route::post('/inventory/usage', [ForemanInventoryController::class, 'storeUsage'])->name('inventory.usage.store');
        Route::post('/inventory/progress', [ForemanInventoryController::class, 'storeProgress'])->name('inventory.progress.store');
    });
});

// Legacy routes (keep for backward compatibility)
Route::middleware(['auth', 'worker'])->prefix('worker')->name('worker.')->group(function () {
    Route::get('/dashboard', [WorkerDashboardController::class, 'index'])->name('dashboard');
    Route::post('/claims', [WorkerDashboardController::class, 'storeClaim'])->name('claims.store');
});

Route::middleware(['auth', 'foreman'])->prefix('foreman')->name('foreman.')->group(function () {
    Route::get('/dashboard', [ForemanDashboardController::class, 'index'])->name('dashboard');
    Route::post('/attendance/bulk', [ForemanDashboardController::class, 'bulkAttendance'])->name('attendance.bulk');
    Route::post('/claims/bulk-action', [ForemanDashboardController::class, 'bulkClaimAction'])->name('claims.bulk-action');
});
