<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BusinessUnitController;
use App\Http\Controllers\Admin\DepartmentController;

// Default route - seamless redirect to login or dashboard
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Dashboard with enhanced middleware
Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Profile page
Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Purchase Request Routes
Route::middleware(['auth', 'verified', 'ensure.business.unit.selected'])->group(function () {
    // Purchase Request Management
    Route::prefix('purchase-requests')->name('purchase-requests.')->group(function () {
        Route::get('/', [PurchaseRequestController::class, 'index'])->name('index');
        
        // Separated PR Flow Routes
        Route::get('/request-number', function () {
            return view('purchase-requests.request-number');
        })->name('request-number');
        Route::get('/create-with-number', function () {
            return view('purchase-requests.create-with-number');
        })->name('create-with-number');
        
        // Legacy single-step route (still available)
        Route::get('/create', [PurchaseRequestController::class, 'create'])->name('create');
        
        Route::post('/', [PurchaseRequestController::class, 'store'])->name('store');
        Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');
        Route::get('/{purchaseRequest}/edit', [PurchaseRequestController::class, 'edit'])->name('edit');
        Route::put('/{purchaseRequest}', [PurchaseRequestController::class, 'update'])->name('update');
        Route::delete('/{purchaseRequest}', [PurchaseRequestController::class, 'destroy'])->name('destroy');
        Route::post('/{purchaseRequest}/submit', [PurchaseRequestController::class, 'submit'])->name('submit');
        Route::post('/{purchaseRequest}/void', [PurchaseRequestController::class, 'void'])->name('void');
        Route::get('/all/list', [PurchaseRequestController::class, 'all'])->name('all');
    });
    
    // Approval Routes
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/', [ApprovalController::class, 'index'])->name('index');
        Route::get('/{prApproval}', [ApprovalController::class, 'show'])->name('show');
        Route::post('/process', [ApprovalController::class, 'process'])->name('process');
        Route::post('/{prApproval}/approve', [ApprovalController::class, 'approve'])->name('approve');
        Route::post('/{prApproval}/reject', [ApprovalController::class, 'reject'])->name('reject');
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
