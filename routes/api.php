<?php

use App\Http\Controllers\Modules\Purchasing\PurchaseRequest\Api\ApprovalController;
use App\Http\Controllers\Modules\Purchasing\PurchaseRequest\Api\PurchaseRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API V1 Routes
Route::prefix('v1')->group(function () {

    // Authentication required for all API routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {

        // Purchase Request API Routes
        Route::prefix('purchase-requests')->name('api.purchase-requests.')->group(function () {
            Route::get('/', [PurchaseRequestController::class, 'index'])->name('index');
            Route::post('/', [PurchaseRequestController::class, 'store'])->name('store');
            Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');
            Route::put('/{purchaseRequest}', [PurchaseRequestController::class, 'update'])->name('update');
            Route::delete('/{purchaseRequest}', [PurchaseRequestController::class, 'destroy'])->name('destroy');

            // PR Actions
            Route::post('/{purchaseRequest}/submit', [PurchaseRequestController::class, 'submit'])->name('submit');
            Route::post('/{purchaseRequest}/void', [PurchaseRequestController::class, 'void'])->name('void');
            Route::get('/{purchaseRequest}/workflow-status', [PurchaseRequestController::class, 'workflowStatus'])->name('workflow-status');
        });

        // Approval API Routes
        Route::prefix('approvals')->name('api.approvals.')->group(function () {
            Route::get('/', [ApprovalController::class, 'index'])->name('index');
            Route::get('/{prApproval}', [ApprovalController::class, 'show'])->name('show');

            // Approval Actions
            Route::post('/process', [ApprovalController::class, 'process'])->name('process');
            Route::post('/{prApproval}/approve', [ApprovalController::class, 'approve'])->name('approve');
            Route::post('/{prApproval}/reject', [ApprovalController::class, 'reject'])->name('reject');

            // Approval Analytics
            Route::get('/statistics', [ApprovalController::class, 'statistics'])->name('statistics');
            Route::get('/history', [ApprovalController::class, 'history'])->name('history');
        });

        // User Profile API
        Route::get('/user', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => $request->user()->load(['roles', 'businessUnits.businessUnit', 'primaryDepartment']),
            ]);
        })->name('api.user.profile');

        // Business Units API
        Route::get('/business-units', function () {
            $businessUnits = \App\Models\Core\BusinessUnit::where('is_active', true)
                ->with(['departments' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $businessUnits,
            ]);
        })->name('api.business-units.index');

        // Departments API
        Route::get('/departments', function (Request $request) {
            $query = \App\Models\Core\Department::where('is_active', true);

            if ($request->filled('business_unit_id')) {
                $query->where('business_unit_id', $request->business_unit_id);
            }

            $departments = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $departments,
            ]);
        })->name('api.departments.index');

    });

    // Public API routes (no authentication required)
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is healthy',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
        ]);
    })->name('api.health');

    // API Documentation endpoint
    Route::get('/docs', function () {
        return response()->json([
            'success' => true,
            'message' => 'NumberSys API Documentation',
            'version' => '1.0.0',
            'endpoints' => [
                'authentication' => [
                    'login' => 'POST /api/auth/login',
                    'logout' => 'POST /api/auth/logout',
                    'refresh' => 'POST /api/auth/refresh',
                ],
                'purchase_requests' => [
                    'list' => 'GET /api/v1/purchase-requests',
                    'create' => 'POST /api/v1/purchase-requests',
                    'show' => 'GET /api/v1/purchase-requests/{id}',
                    'update' => 'PUT /api/v1/purchase-requests/{id}',
                    'delete' => 'DELETE /api/v1/purchase-requests/{id}',
                    'submit' => 'POST /api/v1/purchase-requests/{id}/submit',
                    'void' => 'POST /api/v1/purchase-requests/{id}/void',
                    'workflow_status' => 'GET /api/v1/purchase-requests/{id}/workflow-status',
                ],
                'approvals' => [
                    'list' => 'GET /api/v1/approvals',
                    'show' => 'GET /api/v1/approvals/{id}',
                    'process' => 'POST /api/v1/approvals/process',
                    'approve' => 'POST /api/v1/approvals/{id}/approve',
                    'reject' => 'POST /api/v1/approvals/{id}/reject',
                    'statistics' => 'GET /api/v1/approvals/statistics',
                    'history' => 'GET /api/v1/approvals/history',
                ],
                'reference_data' => [
                    'user_profile' => 'GET /api/v1/user',
                    'business_units' => 'GET /api/v1/business-units',
                    'departments' => 'GET /api/v1/departments',
                ],
            ],
            'authentication' => [
                'type' => 'Bearer Token (Laravel Sanctum)',
                'header' => 'Authorization: Bearer {token}',
                'required_headers' => [
                    'X-Business-Unit-ID' => 'Current business unit ID for context',
                ],
            ],
            'rate_limiting' => '60 requests per minute per user',
            'response_format' => [
                'success_response' => [
                    'success' => true,
                    'data' => '(response data)',
                    'meta' => '(pagination meta for paginated responses)',
                    'links' => '(pagination links for paginated responses)',
                ],
                'error_response' => [
                    'success' => false,
                    'message' => 'Error message',
                    'error' => '(detailed error for debugging)',
                    'errors' => '(validation errors for 422 responses)',
                ],
            ],
        ]);
    })->name('api.docs');
});
