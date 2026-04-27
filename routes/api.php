<?php

use App\Http\Controllers\Modules\Activity\ActivityReportingController;
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
    // Using Sanctum for SPA authentication (stateful for same-domain, token for external)
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

        // Activity Reporting API Routes
        Route::prefix('activity')->name('api.activity.')->middleware('activity.reporting.access')->group(function () {
            // Dashboard - Role-based data returned by controller
            Route::get('/dashboard', [ActivityReportingController::class, 'dashboard'])->name('dashboard');

            // BOD-Only Metrics Routes (requires view-reports permission)
            // These routes provide aggregated data across all business units
            // Only accessible by top management (General Manager, Director, CEO, Finance Manager, Super Admin)
            Route::middleware('can:view-reports')->group(function () {
                Route::get('/metrics/business-units', [ActivityReportingController::class, 'businessUnitMetrics'])
                    ->name('metrics.business-units');
                Route::get('/metrics/strategic-focus', [ActivityReportingController::class, 'strategicFocus'])
                    ->name('metrics.strategic-focus');
            });

            // Manager/Team Metrics Routes (accessible by managers and above)
            Route::get('/metrics/workload-heatmap', [ActivityReportingController::class, 'workloadHeatmap'])
                ->name('metrics.workload-heatmap');

            // Validation Queue Routes (for managers to review flagged tasks)
            Route::get('/validations', [ActivityReportingController::class, 'validationQueue'])->name('validations.index');
            Route::post('/validations/{id}/approve', [ActivityReportingController::class, 'approveValidation'])->name('validations.approve');
            Route::post('/validations/{id}/reject', [ActivityReportingController::class, 'rejectValidation'])->name('validations.reject');
        });

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
            'message' => 'OASIS API Documentation',
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
                'activity_reporting' => [
                    'dashboard' => [
                        'endpoint' => 'GET /api/v1/activity/dashboard',
                        'description' => 'Get role-based dashboard data (BOD/Manager/Employee)',
                        'params' => 'start_date, end_date, business_unit_id, department_id',
                    ],
                    'business_unit_metrics' => [
                        'endpoint' => 'GET /api/v1/activity/metrics/business-units',
                        'description' => 'Get aggregated BU metrics (BOD only)',
                        'params' => 'start_date, end_date',
                        'permission' => 'view-reports',
                    ],
                    'strategic_focus' => [
                        'endpoint' => 'GET /api/v1/activity/metrics/strategic-focus',
                        'description' => 'Get activity type distribution for Treemap (BOD only)',
                        'params' => 'start_date, end_date, business_unit_id',
                        'permission' => 'view-reports',
                    ],
                    'workload_heatmap' => [
                        'endpoint' => 'GET /api/v1/activity/metrics/workload-heatmap',
                        'description' => 'Get team workload heatmap data',
                        'params' => 'business_unit_id (required), department_id, start_date, end_date',
                    ],
                    'validation_queue' => [
                        'endpoint' => 'GET /api/v1/activity/validations',
                        'description' => 'Get flagged tasks for manager review',
                        'params' => 'business_unit_id (required), department_id, status, validation_type',
                    ],
                    'approve_validation' => [
                        'endpoint' => 'POST /api/v1/activity/validations/{id}/approve',
                        'description' => 'Approve a flagged task',
                        'body' => '{"notes": "optional approval notes"}',
                    ],
                    'reject_validation' => [
                        'endpoint' => 'POST /api/v1/activity/validations/{id}/reject',
                        'description' => 'Reject a flagged task',
                        'body' => '{"reason": "required rejection reason"}',
                    ],
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
            'documentation_links' => [
                'activity_reporting_api' => 'docs/activity-module/api-reference.md',
                'database_schema' => 'docs/activity-module/database-schema.md',
                'api_testing_guide' => 'docs/api-testing/README.md',
            ],
        ]);
    })->name('api.docs');
});
