<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BusinessUnitController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Modules\Purchasing\PurchaseRequest\ApprovalController;
use App\Http\Controllers\Modules\Purchasing\PurchaseRequest\PurchaseRequestController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Default route - seamless redirect to login or dashboard
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Public routes (no authentication required)
Route::get('/purchase-requests/{pr}/public', [ApprovalController::class, 'publicView'])->name('purchase-requests.public');

// Public PDF route for browsershot (no auth middleware)
Route::get('/purchase-requests/{purchaseRequest}/pdf-public', [PurchaseRequestController::class, 'pdfPublic'])->name('purchase-requests.pdf-public');

// Public download PDF route for browsershot (no auth middleware)
Route::get('/purchase-requests/{purchaseRequest}/download-pdf-public', [PurchaseRequestController::class, 'downloadPdfPublic'])->name('purchase-requests.download-pdf-public');

// Stock Request Public Routes (no authentication required)
Route::get('/stock-requests/{sr}/public', [\App\Http\Controllers\Modules\Purchasing\StockRequest\StockApprovalController::class, 'publicView'])->name('stock-requests.public');

// Public PDF routes for stock requests
Route::get('/stock-requests/{stockRequest}/pdf-public', [\App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'pdfPublic'])->name('stock-requests.pdf-public');
Route::get('/stock-requests/{stockRequest}/download-pdf-public', [\App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'downloadPdfPublic'])->name('stock-requests.download-pdf-public');

// Stock Request public approval routes (signed URL with expiry)
Route::get('/stock-approvals/{approval}/public', [\App\Http\Controllers\Modules\Purchasing\StockRequest\StockApprovalController::class, 'showPublicApproval'])
    ->middleware('signed')
    ->name('stock-approvals.public.approve');

// POST route for stock approval (throttle limit for security)
Route::post('/stock-approvals/{approval}/public/process', [\App\Http\Controllers\Modules\Purchasing\StockRequest\StockApprovalController::class, 'processPublicApproval'])
    ->middleware('throttle:5,1')
    ->name('stock-approvals.public.process');

// Public approval routes (signed URL with expiry)
Route::get('/approvals/{approval}/public', [ApprovalController::class, 'showPublicApproval'])
    ->middleware('signed')
    ->name('approvals.public.approve');

// POST route tidak pakai 'signed' middleware karena form POST tidak support signed URL
// Security: validated by approval status checks in controller + throttle limit
Route::post('/approvals/{approval}/public/process', [ApprovalController::class, 'processPublicApproval'])
    ->middleware('throttle:5,1')
    ->name('approvals.public.process');

// ============================================================================
// DEBUG ROUTES - Only active in local environment
// ============================================================================
if (app()->environment('local')) {
    // Browsershot Test - Simple page for testing PDF generation
    Route::get('/test-browsershot', function () {
        return '<html><body><h1>Test Page</h1><p>This is a simple test page for Browsershot.</p></body></html>';
    })->name('test.browsershot');
}

// Dashboard with enhanced middleware
Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Profile page
Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Legacy Purchase Request Routes (Backward Compatibility)
Route::middleware(['auth', 'verified', 'ensure.business.unit.selected'])->group(function () {
    // Purchase Request Management
    Route::prefix('purchase-requests')->name('purchase-requests.')->group(function () {
        // My History - Livewire for real-time updates
        Route::get('/', function () {
            return view('purchasing.purchase-requests.index-livewire');
        })->name('index');

        // Create Route - Loads Livewire component for creating new PR
        Route::get('/create', function () {
            return view('purchasing.purchase-requests.create');
        })->name('create');

        // View/Edit Routes (Livewire handles form submission)
        Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');
        Route::get('/{purchaseRequest}/edit', [PurchaseRequestController::class, 'edit'])->name('edit');

        // Action Routes (handled by controller)
        Route::delete('/{purchaseRequest}', [PurchaseRequestController::class, 'destroy'])->name('destroy');
        Route::post('/{purchaseRequest}/resubmit', [PurchaseRequestController::class, 'resubmit'])->name('resubmit');
        Route::post('/{purchaseRequest}/void', [PurchaseRequestController::class, 'void'])->name('void');
        Route::post('/{purchaseRequest}/mark-offline-approved', [PurchaseRequestController::class, 'markOfflineApproved'])->name('mark-offline-approved');

        // PDF Routes
        Route::get('/{purchaseRequest}/pdf', [PurchaseRequestController::class, 'pdf'])->name('pdf');
        Route::get('/{purchaseRequest}/download-pdf', [PurchaseRequestController::class, 'downloadPdf'])->name('download-pdf');

        // List all PRs (for admin/manager view)
        Route::get('/all/list', [PurchaseRequestController::class, 'all'])->name('all');
    });

    // Approval Routes
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/{prApproval}', [ApprovalController::class, 'show'])->name('show');
        Route::post('/{prApproval}/process', [ApprovalController::class, 'process'])->name('process');
        Route::get('/{prApproval}/qr-code', [ApprovalController::class, 'generateQrCode'])->name('qr-code');
    });

    // PR Number Reservations Routes (Continue/Void only - index page removed)
    Route::prefix('pr-numbers')->name('pr-numbers.')->group(function () {
        Route::get('/{reservation}/continue', [\App\Http\Controllers\PrNumberReservationController::class, 'continueToForm'])->name('continue');
        Route::post('/{reservation}/void', [\App\Http\Controllers\PrNumberReservationController::class, 'void'])->name('void');
    });

    // ============================================================================
    // Stock Request Approval Routes (authenticated)
    // ============================================================================
    Route::prefix('stock-approvals')->name('stock-approvals.')->group(function () {
        Route::get('/', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockApprovalController::class, 'index'])->name('index');
        Route::get('/{approval}', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockApprovalController::class, 'show'])->name('show');
        Route::post('/{approval}/process', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockApprovalController::class, 'process'])->name('process');
        Route::get('/{approval}/qr-code', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockApprovalController::class, 'generateQrCode'])->name('qr-code');
    });

    // ============================================================================
    // Stock Request Routes (v3)
    // ============================================================================
    Route::prefix('stock-requests')->name('stock-requests.')->group(function () {
        // List Routes - Livewire for real-time updates
        Route::get('/', function () {
            return view('purchasing.stock-requests.index-livewire');
        })->name('index');
        Route::get('/all/list', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'all'])->name('all');

        // Create Route - Loads Livewire component for creating new Stock Request
        Route::get('/create', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'create'])->name('create');

        // View/Edit Routes
        Route::get('/{stockRequest}', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'show'])->name('show');
        Route::get('/{stockRequest}/edit', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'edit'])->name('edit');

        // Action Routes
        Route::delete('/{stockRequest}', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'destroy'])->name('destroy');
        Route::post('/{stockRequest}/resubmit', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'resubmit'])->name('resubmit');
        Route::post('/{stockRequest}/void', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'void'])->name('void');
        Route::post('/{stockRequest}/mark-offline-approved', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'markOfflineApproved'])->name('mark-offline-approved');

        // PDF Routes (authenticated)
        Route::get('/{stockRequest}/download-pdf', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'downloadPdf'])->name('download-pdf');
    });

    // ============================================================================
    // Purchasing Combined Routes (v3.5)
    // ============================================================================
    Route::prefix('purchasing')->name('purchasing.')->group(function () {
        Route::get('/all-requests', [\App\Http\Controllers\Modules\Purchasing\PurchasingController::class, 'allRequests'])->name('all-requests');
        
        // Purchasing Admin Routes
        Route::prefix('admin')->name('admin.')->middleware('can:access-purchasing-admin')->group(function () {
            // Dashboard
            Route::get('/dashboard', \App\Livewire\Modules\Purchasing\Admin\AdminDashboard::class)->name('dashboard');
            
            // Task Management
            Route::get('/tasks', \App\Livewire\Modules\Purchasing\Admin\TaskList::class)->name('tasks');
            Route::get('/tasks/{taskId}', \App\Livewire\Modules\Purchasing\Admin\TaskDetail::class)->name('tasks.show');
            
            // Reports
            Route::get('/department-report', \App\Livewire\Modules\Purchasing\Admin\DepartmentReport::class)->name('department-report');
            Route::get('/consolidated-report', \App\Livewire\Modules\Purchasing\Admin\ConsolidatedReport::class)->name('consolidated-report');
            
            // Audit History Routes
            Route::get('/audit-history', \App\Livewire\Modules\Purchasing\Admin\AuditHistory::class)->name('audit-history');
            Route::get('/department-audit-history', \App\Livewire\Modules\Purchasing\Admin\DepartmentAuditHistory::class)->name('department-audit-history');
            Route::get('/personal-task-history', \App\Livewire\Modules\Purchasing\Admin\PersonalTaskHistory::class)->name('personal-task-history');
            
            // SLA Settings (Super Admin only)
            Route::get('/sla-settings', [\App\Http\Controllers\Admin\SlaSettingsController::class, 'index'])
                ->middleware('admin.access')
                ->name('sla-settings');
            Route::post('/sla-settings', [\App\Http\Controllers\Admin\SlaSettingsController::class, 'update'])
                ->middleware('admin.access')
                ->name('sla-settings.update');
        });
    });

    // ============================================================================
    // Sales CRM Routes (v2.5)
    // ============================================================================
    Route::prefix('sales-crm')->name('sales-crm.')->middleware('can:view_activities')->group(function () {
        // Activities
        Route::prefix('activities')->name('activities.')->group(function () {
            Route::get('/', \App\Livewire\Modules\SalesCrm\ActivityIndex::class)->name('index');
            Route::get('/create', \App\Livewire\Modules\SalesCrm\ActivityForm::class)->name('create');
            Route::get('/{activityId}', \App\Livewire\Modules\SalesCrm\ActivityForm::class)->name('show');
            Route::get('/{activityId}/edit', \App\Livewire\Modules\SalesCrm\ActivityForm::class)->name('edit');
        });

        // Contacts
        Route::prefix('contacts')->name('contacts.')->middleware('can:view_contacts')->group(function () {
            Route::get('/', \App\Livewire\Modules\SalesCrm\ContactIndex::class)->name('index');
            Route::get('/create', \App\Livewire\Modules\SalesCrm\ContactForm::class)->name('create');
            Route::get('/{contactId}', \App\Livewire\Modules\SalesCrm\ContactForm::class)->name('show');
            Route::get('/{contactId}/edit', \App\Livewire\Modules\SalesCrm\ContactForm::class)->name('edit');
        });
    });

    // Reports Routes (Top Management Only - Coming Soon)
    Route::prefix('reports')->name('reports.')->middleware('can:view-reports')->group(function () {
        Route::get('/purchase-requests', function () {
            return view('reports.purchase-requests', [
                'message' => 'Report feature will be available soon for top management.',
            ]);
        })->name('purchase-requests');
        Route::get('/approvals', function () {
            return view('reports.approvals', [
                'message' => 'Report feature will be available soon for top management.',
            ]);
        })->name('approvals');
    });

    // Admin Routes (require super admin access)
    Route::prefix('admin')->name('admin.')->middleware('admin.access')->group(function () {
        // Admin Dashboard
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/system-health', [AdminController::class, 'systemHealth'])->name('system-health');

        // User Management (Super Admin Only)
        Route::resource('users', \App\Http\Controllers\Admin\UserManagementController::class);
        Route::get('business-units/{businessUnit}/departments', [\App\Http\Controllers\Admin\UserManagementController::class, 'getDepartments'])->name('business-units.departments');
        Route::get('departments/{department}/positions', [\App\Http\Controllers\Admin\UserManagementController::class, 'getPositions'])->name('departments.positions');

        // Business Unit Management
        Route::resource('business-units', BusinessUnitController::class);
        Route::patch('business-units/{businessUnit}/toggle-status', [BusinessUnitController::class, 'toggleStatus'])->name('business-units.toggle-status');
        Route::get('business-units/{businessUnit}/configuration', [BusinessUnitController::class, 'getConfiguration'])->name('business-units.configuration');
        Route::post('business-units/{businessUnit}/configuration', [BusinessUnitController::class, 'updateConfiguration'])->name('business-units.update-configuration');

        // Department Management
        Route::resource('departments', DepartmentController::class);
        Route::get('departments/{department}/purchasing-config', [DepartmentController::class, 'purchasingConfig'])->name('departments.purchasing-config');

        // PR Category Management
        Route::resource('pr-categories', \App\Http\Controllers\Admin\PrCategoryController::class);

        // Notification Settings (Super Admin Only)
        Route::get('/notification-settings', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'index'])->name('notification-settings.index');
        Route::post('/notification-settings', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'update'])->name('notification-settings.update');
        Route::post('/notification-settings/test', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'sendTest'])
            ->middleware('throttle:3,1')
            ->name('notification-settings.test');
        Route::get('/notification-settings/statistics', [\App\Http\Controllers\Admin\NotificationSettingsController::class, 'statistics'])->name('notification-settings.statistics');

        // SLA Settings (Super Admin Only)
        Route::get('/sla-settings', [\App\Http\Controllers\Admin\SlaSettingsController::class, 'index'])->name('sla-settings.index');
        Route::post('/sla-settings', [\App\Http\Controllers\Admin\SlaSettingsController::class, 'update'])->name('sla-settings.update');

        // Number Sequence Management (placeholder routes)
        Route::get('/number-sequences', function () {
            return view('admin.number-sequences.index');
        })->name('number-sequences.index');

        // Workflow Management (placeholder routes)
        Route::get('/workflows', function () {
            return view('admin.workflows.index');
        })->name('workflows.index');
    });
});

require __DIR__.'/auth.php';
