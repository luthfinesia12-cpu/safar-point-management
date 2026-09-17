<?php

use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentWorkflowController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MasterDataImportController;
use App\Http\Controllers\NotificationDeliveryController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ProductScanController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
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

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.change');
    Route::put('/password/change', [PasswordController::class, 'update'])->name('password.update');
    Route::middleware('password.changed')->group(function () {
        Route::get('/', DashboardController::class)->middleware('permission:dashboard.view')->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update')->name('users.update');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->middleware('permission:users.deactivate')->name('users.toggle');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('permission:users.reset-password')->name('users.reset-password');
        Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.manage')->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.manage')->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.manage')->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.manage')->name('roles.destroy');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit.view')->name('audit.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->middleware('permission:audit.view')->name('audit.show');
        Route::get('/notification-deliveries', [NotificationDeliveryController::class, 'index'])->middleware('permission:audit.view')->name('notification-deliveries.index');
        Route::post('/notification-deliveries/{notificationDelivery}/resend', [NotificationDeliveryController::class, 'resend'])->middleware('permission:notifications.resend')->name('notification-deliveries.resend');
        Route::get('/settings/company', [CompanySettingController::class, 'edit'])->middleware('permission:settings.manage')->name('settings.company');
        Route::put('/settings/company', [CompanySettingController::class, 'update'])->middleware('permission:settings.manage')->name('settings.company.update');
        Route::get('/master/bank-accounts', [MasterDataController::class, 'index'])->middleware('permission:bank-accounts.manage')->name('master-data.bank-accounts.index')->defaults('type', 'bank-accounts');
        Route::post('/master/bank-accounts', [MasterDataController::class, 'store'])->middleware('permission:bank-accounts.manage')->name('master-data.bank-accounts.store')->defaults('type', 'bank-accounts');
        Route::put('/master/bank-accounts/{id}', [MasterDataController::class, 'update'])->middleware('permission:bank-accounts.manage')->name('master-data.bank-accounts.update')->defaults('type', 'bank-accounts');
        Route::get('/master/{type}', [MasterDataController::class, 'index'])->middleware('permission:master-data.manage')->name('master-data.index');
        Route::get('/products/scan-lookup', ProductScanController::class)->name('products.scan-lookup');
        Route::post('/master/{type}', [MasterDataController::class, 'store'])->middleware('permission:master-data.manage')->name('master-data.store');
        Route::put('/master/{type}/{id}', [MasterDataController::class, 'update'])->middleware('permission:master-data.manage')->name('master-data.update');
        Route::get('/master/{type}/import-template.csv', [MasterDataImportController::class, 'template'])->middleware('permission:master-data.import')->name('master-data.import-template');
        Route::post('/master/{type}/import/preview', [MasterDataImportController::class, 'preview'])->middleware('permission:master-data.import')->name('master-data.import-preview');
        Route::post('/master-imports/{batch}/commit', [MasterDataImportController::class, 'commit'])->middleware('permission:master-data.import')->name('master-data.import-commit');
        Route::get('/purchase-requests', [PurchaseRequestController::class, 'index'])->middleware('permission:purchase-requests.create')->name('purchase-requests.index');
        Route::post('/purchase-requests', [PurchaseRequestController::class, 'store'])->middleware('permission:purchase-requests.create')->name('purchase-requests.store');
        Route::post('/purchase-requests/{purchaseRequest}/submit', [PurchaseRequestController::class, 'submit'])->middleware('permission:purchase-requests.submit')->name('purchase-requests.submit');
        Route::post('/purchase-requests/{purchaseRequest}/decide', [PurchaseRequestController::class, 'decide'])->middleware('permission:purchase-requests.approve')->name('purchase-requests.decide');
        Route::post('/purchase-requests/{purchaseRequest}/cancel', [DocumentWorkflowController::class, 'cancelPurchaseRequest'])->middleware('permission:purchase-requests.cancel')->name('purchase-requests.cancel');
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->middleware('permission:purchase-orders.create')->name('purchase-orders.index');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->middleware('permission:purchase-orders.create')->name('purchase-orders.store');
        Route::post('/purchase-orders/{purchaseOrder}/revise', [PurchaseOrderController::class, 'revise'])->middleware('permission:purchase-orders.revise')->name('purchase-orders.revise');
        Route::post('/purchase-order-revisions/{purchaseOrderRevision}/decide', [PurchaseOrderController::class, 'decideRevision'])->middleware('permission:purchase-orders.approve')->name('purchase-order-revisions.decide');
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [DocumentWorkflowController::class, 'cancelPurchaseOrder'])->middleware('permission:purchase-orders.cancel')->name('purchase-orders.cancel');
        Route::post('/purchase-orders/{purchaseOrder}/close-short', [DocumentWorkflowController::class, 'closeShort'])->middleware('permission:purchase-orders.close-short')->name('purchase-orders.close-short');
        Route::post('/document-workflow-requests/{workflow}/decide', [DocumentWorkflowController::class, 'decide'])->middleware('permission:purchase-orders.approve')->name('document-workflow-requests.decide');
        Route::get('/goods-receipts', [GoodsReceiptController::class, 'index'])->middleware('permission:goods-receipts.create')->name('goods-receipts.index');
        Route::post('/goods-receipts', [GoodsReceiptController::class, 'store'])->middleware('permission:goods-receipts.create')->name('goods-receipts.store');
        Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:payments.create')->name('payments.index');
        Route::post('/payments', [PaymentController::class, 'store'])->middleware('permission:payments.create')->name('payments.store');
        Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->middleware('permission:payments.verify')->name('payments.verify');
        Route::get('/adjustments', [AdjustmentController::class, 'index'])->middleware('permission:adjustments.create')->name('adjustments.index');
        Route::post('/adjustments', [AdjustmentController::class, 'store'])->middleware('permission:adjustments.create')->name('adjustments.store');
        Route::post('/adjustments/{adjustment}/decide', [AdjustmentController::class, 'decide'])->middleware('permission:adjustments.approve')->name('adjustments.decide');
        Route::post('/attachments', [AttachmentController::class, 'store'])->middleware('permission:attachments.create')->name('attachments.store');
        Route::get('/exports/{type}.csv', [ExportController::class, 'csv'])->middleware('permission:reports.view')->name('exports.csv');
        Route::get('/exports/{type}.xlsx', [ExportController::class, 'xlsx'])->middleware('permission:reports.view')->name('exports.xlsx');
        Route::get('/exports/{type}.pdf', [ExportController::class, 'pdf'])->middleware('permission:reports.view')->name('exports.pdf');
    });
});
