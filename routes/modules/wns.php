<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ApprovalController;

/*
|--------------------------------------------------------------------------
| WNS Module Routes
|--------------------------------------------------------------------------
|
| Routes specific to WNS (Werkudara Nusantara Sejahtera) business unit
| All purchase request related functionality for WNS module
|
*/

Route::middleware(['auth', 'verified'])->prefix('wns')->name('wns.')->group(function () {
    
    // Purchase Requests Module
    Route::prefix('purchase-requests')->name('purchase-requests.')->group(function () {
        
        // Create Purchase Request
        Route::get('/create', function () {
            return view('purchase-requests.create');
        })->name('create');
        
        // List Purchase Requests
        Route::get('/', function () {
            return view('purchase-requests.index');
        })->name('index');
        
        // Show Purchase Request
        Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])
            ->name('show');
        
        // Edit Purchase Request
        Route::get('/{purchaseRequest}/edit', [PurchaseRequestController::class, 'edit'])
            ->name('edit');
        
        // Update Purchase Request
        Route::put('/{purchaseRequest}', [PurchaseRequestController::class, 'update'])
            ->name('update');
        
        // Delete Purchase Request
        Route::delete('/{purchaseRequest}', [PurchaseRequestController::class, 'destroy'])
            ->name('destroy');
        
        // All Purchase Requests (for admins)
        Route::get('/all/admin-view', function () {
            return view('purchase-requests.all');
        })->name('all');
        
        // Approval Routes
        Route::get('/{pr}/approve/{approval}', [ApprovalController::class, 'show'])
            ->name('approval');
        
        Route::post('/{pr}/approve/{approval}', [ApprovalController::class, 'processApproval'])
            ->name('approval.process');
    });
    
    // Reports Module
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/purchase-requests', function () {
            return view('reports.purchase-requests');
        })->name('purchase-requests');
    });
});