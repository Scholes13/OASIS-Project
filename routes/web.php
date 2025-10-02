<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BusinessUnitController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\PurchaseRequestController;
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
        Route::get('/', [PurchaseRequestController::class, 'index'])->name('index');

        // Create Route - Loads Livewire component for creating new PR
        Route::get('/create', function () {
            return view('purchase-requests.create');
        })->name('create');

        // View/Edit Routes (Livewire handles form submission)
        Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');
        Route::get('/{purchaseRequest}/edit', [PurchaseRequestController::class, 'edit'])->name('edit');
        
        // Action Routes (handled by controller)
        Route::delete('/{purchaseRequest}', [PurchaseRequestController::class, 'destroy'])->name('destroy');
        Route::post('/{purchaseRequest}/resubmit', [PurchaseRequestController::class, 'resubmit'])->name('resubmit');
        Route::post('/{purchaseRequest}/void', [PurchaseRequestController::class, 'void'])->name('void');
        
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

    // PR Number Reservations Routes
    Route::prefix('pr-numbers')->name('pr-numbers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PrNumberReservationController::class, 'index'])->name('index');
        Route::get('/{reservation}/continue', [\App\Http\Controllers\PrNumberReservationController::class, 'continueToForm'])->name('continue');
        Route::post('/{reservation}/void', [\App\Http\Controllers\PrNumberReservationController::class, 'void'])->name('void');
    });

    // Reports Routes (placeholder)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/purchase-requests', function () {
            return view('reports.purchase-requests');
        })->name('purchase-requests');
        Route::get('/approvals', function () {
            return view('reports.approvals');
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
