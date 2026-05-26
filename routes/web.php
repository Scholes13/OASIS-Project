<?php

use App\Http\Controllers\Admin\ActivityConfigurationController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BusinessUnitController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\ITSupportAssignmentController;
use App\Http\Controllers\Api\DepartmentController as ApiDepartmentController;
use App\Http\Controllers\Modules\Activity\TaskCommentController;
use App\Http\Controllers\Modules\CashflowProjection\CashflowProjectionController;
use App\Http\Controllers\Modules\Purchasing\PurchaseRequest\ApprovalController;
use App\Http\Controllers\Modules\Purchasing\PurchaseRequest\PurchaseRequestController;
use App\Http\Controllers\Modules\Ticket\KnowledgeBaseController;
use App\Http\Controllers\Modules\Ticket\KnowledgeCategoryController;
use App\Http\Controllers\Modules\Ticket\TicketCategoryController;
use App\Http\Controllers\Modules\Ticket\TicketController;
use App\Http\Controllers\Modules\Ticket\TicketDashboardController;
use App\Http\Controllers\Modules\Ticket\TicketReportingController;
use App\Http\Controllers\Modules\Ticket\UserTicketController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Default route - seamless redirect to login or dashboard
Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// ============================================================================
// API Routes for React/Inertia Frontend
// ============================================================================
Route::prefix('api')->middleware(['auth'])->group(function () {
    // Business Unit Switch API (Requirements: 9.1)
    Route::post('/business-unit/switch', [\App\Http\Controllers\Api\BusinessUnitController::class, 'switch'])
        ->name('api.business-unit.switch');

    // Department Switch API for users with multiple departments in the active BU
    Route::post('/department/switch', [ApiDepartmentController::class, 'switch'])
        ->name('api.department.switch');
    // Error Logging API (Requirement 15.5: Log frontend errors to server)
    Route::post('/error-logs', [\App\Http\Controllers\ErrorLogController::class, 'store'])
        ->name('api.error-logs.store');

    Route::post('/error-logs/batch', [\App\Http\Controllers\ErrorLogController::class, 'storeBatch'])
        ->name('api.error-logs.batch');
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

    // Error Logging Test - Test error logging system
    Route::get('/error-test', function () {
        return \Inertia\Inertia::render('ErrorTest');
    })->middleware(['auth'])->name('test.error-logging');
}

// Docs & Help page (authenticated)
Route::get('docs-help', [\App\Http\Controllers\DocsHelpController::class, 'index'])
    ->middleware(['auth'])
    ->name('docs-help');

// Profile page (React/Inertia)
Route::middleware(['auth'])->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ProfileController::class, 'show'])->name('index');
    Route::post('/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('password');
});

// Profile route alias for backward compatibility with navigation
Route::middleware(['auth'])->get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile');

// Main Dashboard Route
Route::middleware(['auth', 'verified', 'ensure.business.unit.selected'])->group(function () {
    // Main Dashboard (Quick Access after login)
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationCenterController::class, 'index'])->name('index');
        Route::get('/recent', [\App\Http\Controllers\NotificationCenterController::class, 'recent'])->name('recent');
        Route::post('/mark-all-read', [\App\Http\Controllers\NotificationCenterController::class, 'markAllRead'])->name('mark-all-read');
        Route::get('/{notification}/open', [\App\Http\Controllers\NotificationCenterController::class, 'open'])->name('open');
    });

    // Purchase Request Management
    Route::prefix('purchase-requests')->name('purchase-requests.')->group(function () {
        // My History - Inertia/React page
        Route::get('/', [PurchaseRequestController::class, 'index'])->name('index');

        // Create Route - Inertia/React page
        Route::get('/create', [PurchaseRequestController::class, 'create'])->name('create');

        // Store Route - Handle form submission
        Route::post('/', [PurchaseRequestController::class, 'store'])->name('store');

        // View Route - Inertia/React page
        Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show'])->name('show');

        // Edit Route - Inertia/React page
        Route::get('/{purchaseRequest}/edit', [PurchaseRequestController::class, 'editInertia'])->name('edit');

        // Update Route - Handle form submission
        Route::put('/{purchaseRequest}', [PurchaseRequestController::class, 'update'])->name('update');

        // Action Routes (handled by controller)
        Route::delete('/{purchaseRequest}', [PurchaseRequestController::class, 'destroy'])->name('destroy');
        Route::post('/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve'])->name('approve');
        Route::post('/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject'])->name('reject');
        Route::post('/{purchaseRequest}/resubmit', [PurchaseRequestController::class, 'resubmit'])->name('resubmit');
        Route::post('/{purchaseRequest}/void', [PurchaseRequestController::class, 'void'])->name('void');
        Route::post('/{purchaseRequest}/mark-offline-approved', [PurchaseRequestController::class, 'markOfflineApproved'])->name('mark-offline-approved');
        Route::post('/{purchaseRequest}/resend-approval-email', [PurchaseRequestController::class, 'resendApprovalEmail'])->name('resend-approval-email');
        Route::get('/{purchaseRequest}/offline-approval-document', [PurchaseRequestController::class, 'offlineApprovalDocument'])->name('offline-approval-document');

        // PDF Routes
        Route::get('/{purchaseRequest}/pdf', [PurchaseRequestController::class, 'pdf'])->name('pdf');
        Route::get('/{purchaseRequest}/supporting-document', [PurchaseRequestController::class, 'supportingDocument'])->name('supporting-document');
        Route::get('/{purchaseRequest}/supporting-document/download', [PurchaseRequestController::class, 'downloadSupportingDocument'])->name('supporting-document.download');
        Route::get('/{purchaseRequest}/download-pdf', [PurchaseRequestController::class, 'downloadPdf'])->name('download-pdf');

        // List all PRs (for admin/manager view) - Livewire for now
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
        Route::get('/{reservation}/continue', [\App\Http\Controllers\Modules\Purchasing\PurchaseRequest\PrNumberReservationController::class, 'continueToForm'])->name('continue');
        Route::post('/{reservation}/void', [\App\Http\Controllers\Modules\Purchasing\PurchaseRequest\PrNumberReservationController::class, 'void'])->name('void');
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
        // List Routes - Inertia for modern SPA experience
        Route::get('/', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'index'])->name('index');

        // Create Route - Inertia form for creating new Stock Request
        Route::get('/create', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'createInertia'])->name('create');
        Route::post('/', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'store'])->name('store');

        // View/Edit Routes
        Route::get('/{stockRequest}', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'showInertia'])->name('show');
        Route::get('/{stockRequest}/edit', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'editInertia'])->name('edit');
        Route::put('/{stockRequest}', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'update'])->name('update');

        // Action Routes
        Route::delete('/{stockRequest}', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'destroy'])->name('destroy');
        Route::post('/{stockRequest}/resubmit', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'resubmit'])->name('resubmit');
        Route::post('/{stockRequest}/void', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'void'])->name('void');
        Route::post('/{stockRequest}/mark-offline-approved', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'markOfflineApproved'])->name('mark-offline-approved');
        Route::post('/{stockRequest}/resend-approval-email', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'resendApprovalEmail'])->name('resend-approval-email');
        Route::get('/{stockRequest}/offline-approval-document', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'offlineApprovalDocument'])->name('offline-approval-document');

        // PDF Routes (authenticated)
        Route::get('/{stockRequest}/download-pdf', [App\Http\Controllers\Modules\Purchasing\StockRequest\StockRequestController::class, 'downloadPdf'])->name('download-pdf');
    });

    // ============================================================================
    // Purchasing Combined Routes (v3.5)
    // ============================================================================
    Route::prefix('purchasing')->name('purchasing.')->group(function () {
        // Purchasing Dashboard (Inertia/React)
        // Requirements: 14.2
        Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/all-requests', [\App\Http\Controllers\Modules\Purchasing\PurchasingController::class, 'allRequests'])->name('all-requests');

        // Purchasing Admin Routes (Inertia)
        Route::prefix('admin')->name('admin.')->middleware('can:access-purchasing-admin')->group(function () {
            // Dashboard
            Route::get('/dashboard', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'dashboard'])->name('dashboard');

            // Task Management
            Route::get('/tasks', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'tasks'])->name('tasks');
            Route::get('/tasks/{taskId}', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'taskDetail'])->name('tasks.show');
            Route::post('/tasks/{taskId}/claim', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'claimTask'])->name('tasks.claim');
            Route::post('/tasks/{taskId}/start', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'startTask'])->name('tasks.start');
            Route::put('/tasks/{taskId}/status', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'updateTaskStatus'])->name('tasks.update-status');
            Route::post('/tasks/{taskId}/complete', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'completeTask'])->name('tasks.complete');

            // Task History
            Route::get('/task-history', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'taskHistory'])->name('task-history');
            Route::get('/task-history/export', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'exportTaskHistory'])->name('task-history.export');

            // Reports
            Route::get('/department-report', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'departmentReport'])->name('department-report');
            Route::get('/consolidated-report', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'consolidatedReport'])->middleware('can:view-purchasing-reports')->name('consolidated-report');

            // Audit History Routes
            Route::get('/audit-history', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'auditHistory'])->name('audit-history');
            Route::get('/department-audit-history', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'departmentAuditHistory'])->name('department-audit-history');
            Route::get('/personal-task-history', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'personalTaskHistory'])->name('personal-task-history');
            Route::get('/management-history', [\App\Http\Controllers\Modules\Purchasing\Admin\PurchasingAdminController::class, 'managementHistory'])->name('management-history');

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
    // Sales CRM Routes (v2.5) — DEPRECATED / ARCHIVED
    // ----------------------------------------------------------------------------
    // The Sales CRM module is archived. Controllers, services, models, and
    // migrations remain on disk for reference but the routes only register
    // when the `features.sales_crm` flag is explicitly enabled
    // (env: FEATURE_SALES_CRM=true). With the flag off (default) the URLs
    // return 404 because no route is registered.
    // To reactivate, set FEATURE_SALES_CRM=true and run `php artisan route:cache`.
    // ============================================================================
    if (config('features.sales_crm', false)) {
        Route::prefix('sales-crm')->name('sales-crm.')->middleware('can:view_activities')->group(function () {
            // Activities
            Route::prefix('activities')->name('activities.')->group(function () {
                Route::get('/', [\App\Http\Controllers\SalesCrmController::class, 'activitiesIndex'])->name('index');
                Route::get('/create', [\App\Http\Controllers\SalesCrmController::class, 'activitiesCreate'])->name('create');
                Route::get('/{activity}', [\App\Http\Controllers\SalesCrmController::class, 'activitiesShow'])->name('show');
                Route::get('/{activity}/edit', [\App\Http\Controllers\SalesCrmController::class, 'activitiesEdit'])->name('edit');
                Route::post('/', [\App\Http\Controllers\SalesCrmController::class, 'activitiesStore'])->name('store');
                Route::put('/{activity}', [\App\Http\Controllers\SalesCrmController::class, 'activitiesUpdate'])->name('update');
                Route::delete('/{activity}', [\App\Http\Controllers\SalesCrmController::class, 'activitiesDestroy'])->name('destroy');
            });

            // Contacts
            Route::prefix('contacts')->name('contacts.')->middleware('can:view_contacts')->group(function () {
                Route::get('/', [\App\Http\Controllers\SalesCrmController::class, 'contactsIndex'])->name('index');
                Route::get('/create', [\App\Http\Controllers\SalesCrmController::class, 'contactsCreate'])->name('create');
                Route::get('/{contact}', [\App\Http\Controllers\SalesCrmController::class, 'contactsShow'])->name('show');
                Route::get('/{contact}/edit', [\App\Http\Controllers\SalesCrmController::class, 'contactsEdit'])->name('edit');
                Route::post('/', [\App\Http\Controllers\SalesCrmController::class, 'contactsStore'])->name('store');
                Route::put('/{contact}', [\App\Http\Controllers\SalesCrmController::class, 'contactsUpdate'])->name('update');
                Route::delete('/{contact}', [\App\Http\Controllers\SalesCrmController::class, 'contactsDestroy'])->name('destroy');
            });
        });
    }

    // ============================================================================
    // Activity Tracking Module Routes (INERTIA/REACT)
    // ============================================================================
    Route::prefix('activity')->name('activity.')->group(function () {
        // Redirect root to task
        Route::get('/', fn () => redirect()->route('activity.task.index'));

        // Dashboard (Personal & Department Analytics)
        Route::get('/dashboard', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'dashboard'])->name('dashboard');

        // BOD Reporting Dashboard (Top Management Only)
        Route::get('/reporting', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'reportingDashboard'])
            ->middleware('can:view-reports')
            ->name('reporting');

        // Manager Reporting Dashboard (Managers and above)
        Route::get('/reporting/manager', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'managerDashboard'])
            ->middleware('activity.reporting.access')
            ->name('reporting.manager');

        // Activity Admin routes
        Route::prefix('admin')->name('admin.')->middleware('activity.admin.access')->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\Modules\Activity\ActivityAdminController::class, 'dashboard'])->name('dashboard');
            Route::get('/department/{department}', [\App\Http\Controllers\Modules\Activity\ActivityAdminController::class, 'departmentDetail'])->name('department')->whereNumber('department');
            Route::get('/task/{task}', [\App\Http\Controllers\Modules\Activity\ActivityAdminController::class, 'taskDetail'])->name('task')->whereNumber('task');
            Route::get('/export', [\App\Http\Controllers\Modules\Activity\ActivityAdminController::class, 'export'])->name('export');
            Route::get('/backdate/approvals', [\App\Http\Controllers\Modules\Activity\ActivityAdminController::class, 'backdateApprovals'])->name('backdate.approvals');
            Route::post('/backdate/{id}/approve', [\App\Http\Controllers\Modules\Activity\ActivityAdminController::class, 'approveBackdate'])->name('backdate.approve')->whereNumber('id');
            Route::post('/backdate/{id}/reject', [\App\Http\Controllers\Modules\Activity\ActivityAdminController::class, 'rejectBackdate'])->name('backdate.reject')->whereNumber('id');
        });

        // Task Routes (List, Board, Calendar, Timeline views)
        Route::prefix('task')->name('task.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'index'])->name('index');
            Route::get('/create', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'create'])->name('create');
            Route::get('/export', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'export'])->name('export');
            Route::post('/', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'store'])->name('store');
            Route::get('/{task}', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'show'])->name('show')->whereNumber('task');
            Route::get('/{task}/edit', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'edit'])->name('edit')->whereNumber('task');
            Route::put('/{task}', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'update'])->name('update')->whereNumber('task');
            Route::delete('/{task}', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'destroy'])->name('destroy')->whereNumber('task');

            // Task Comment Routes
            Route::prefix('{task}/comments')->name('comments.')->whereNumber('task')->group(function () {
                Route::post('/', [TaskCommentController::class, 'store'])->name('store')->middleware('throttle:10,1');
                Route::put('/{comment}', [TaskCommentController::class, 'update'])->name('update')->middleware('throttle:10,1')->whereNumber('comment');
                Route::delete('/{comment}', [TaskCommentController::class, 'destroy'])->name('destroy')->middleware('throttle:10,1')->whereNumber('comment');
            });
        });

        // Backdate Permission Routes
        Route::prefix('backdate')->name('backdate.')->group(function () {
            Route::get('/requests', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'backdateRequests'])->name('requests');
            Route::post('/request/submit', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'submitBackdateRequest'])->name('request.submit');
            Route::get('/approvals', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'backdateApprovals'])->name('approvals');
            Route::post('/{id}/approve', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'approveBackdate'])->name('approve')->whereNumber('id');
            Route::post('/{id}/reject', [\App\Http\Controllers\Modules\Activity\ActivityInertiaController::class, 'rejectBackdate'])->name('reject')->whereNumber('id');
        });
    });

    // ============================================================================
    // Cashflow Projection Routes
    // ============================================================================
    Route::prefix('cashflow-projection')->name('cashflow-projection.')->middleware('can:access-cashflow-projection')->group(function () {
        Route::get('/', [CashflowProjectionController::class, 'index'])->name('index');
        Route::get('/entries', [CashflowProjectionController::class, 'entries'])->name('entries');
        Route::get('/settings', [CashflowProjectionController::class, 'settings'])->name('settings');
        Route::get('/export', [CashflowProjectionController::class, 'export'])->name('export');
        Route::post('/line-items', [CashflowProjectionController::class, 'storeLineItem'])->name('line-items.store');
        Route::patch('/line-items/{lineItem}', [CashflowProjectionController::class, 'updateLineItem'])->name('line-items.update');
        Route::post('/finance-inputs', [CashflowProjectionController::class, 'upsertFinanceInput'])->name('finance-inputs.upsert');
        Route::post('/linked-units', [CashflowProjectionController::class, 'storeLinkedUnit'])->name('linked-units.store');
        Route::delete('/line-items/{lineItem}', [CashflowProjectionController::class, 'destroyLineItem'])->name('line-items.destroy');
        Route::delete('/linked-units/{linkedUnit}', [CashflowProjectionController::class, 'destroyLinkedUnit'])->name('linked-units.destroy');

        // Entry Import Routes
        Route::get('/entries/import-template', [CashflowProjectionController::class, 'downloadImportTemplate'])->name('entries.import-template');
        Route::post('/entries/import', [CashflowProjectionController::class, 'importEntries'])->name('entries.import');
    });

    // ============================================================================
    // IT Support — User routes (all authenticated users)
    // ============================================================================
    Route::prefix('it-support')->name('it-support.')->group(function () {
        // Ticket submission
        Route::get('/submit', [UserTicketController::class, 'create'])->name('submit');
        Route::post('/submit', [UserTicketController::class, 'store'])->name('submit.store');

        // My tickets
        Route::get('/my-tickets', [UserTicketController::class, 'myTickets'])->name('my-tickets');
        Route::get('/my-tickets/{ticket}', [UserTicketController::class, 'show'])->name('my-tickets.show');
        Route::post('/my-tickets/{ticket}/comment', [UserTicketController::class, 'addComment'])->name('my-tickets.comment');

        // Authenticated attachment download (requester or IT Support admin in scope)
        Route::get('/tickets/attachments/{attachment}/download', [\App\Http\Controllers\Modules\Ticket\TicketAttachmentController::class, 'download'])
            ->name('tickets.attachments.download');

        // Knowledge base browse (all users)
        Route::get('/knowledge', [KnowledgeBaseController::class, 'browse'])->name('knowledge');
        Route::get('/knowledge/search', [KnowledgeBaseController::class, 'search'])->name('knowledge.search');
        Route::post('/knowledge/suggest', [KnowledgeBaseController::class, 'suggestArticles'])->name('knowledge.suggest');
        Route::get('/knowledge/{slug}', [KnowledgeBaseController::class, 'article'])->name('knowledge.article');
    });

    // ============================================================================
    // IT Support — Admin routes (IT Support admin only)
    // ============================================================================
    Route::middleware(['it.support.access'])
        ->prefix('it-support')
        ->name('it-support.admin.')
        ->group(function () {
            // Dashboard
            Route::get('/dashboard', [TicketDashboardController::class, 'index'])->name('dashboard');

            // SLA Settings
            Route::get('/sla-settings', [TicketDashboardController::class, 'slaSettings'])->name('sla-settings');
            Route::put('/sla-settings', [TicketDashboardController::class, 'updateSlaSettings'])->name('sla-settings.update');

            // All tickets management
            Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
            Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
            Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
            Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
            Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
            Route::post('/tickets/{ticket}/comment', [TicketController::class, 'addComment'])->name('tickets.comment');
            Route::put('/tickets/{ticket}/change-status', [TicketController::class, 'changeStatus'])->name('tickets.changeStatus');
            Route::post('/tickets/{ticket}/assign', [TicketController::class, 'assignTicket'])->name('tickets.assign');
            Route::post('/tickets/{ticket}/link-article', [KnowledgeBaseController::class, 'linkArticle'])->name('tickets.linkArticle');

            // Reporting
            Route::get('/reporting', [TicketReportingController::class, 'index'])->name('reporting');
            Route::get('/reporting/export/excel', [TicketReportingController::class, 'exportExcel'])->name('reporting.exportExcel');
            Route::get('/reporting/export/pdf', [TicketReportingController::class, 'exportPdf'])->name('reporting.exportPdf');

            // Categories
            Route::resource('/categories', TicketCategoryController::class)->names('categories')->except(['show']);

            // Knowledge base admin
            Route::get('/knowledge/manage', [KnowledgeBaseController::class, 'adminIndex'])->name('knowledge.index');
            Route::get('/knowledge/manage/create', [KnowledgeBaseController::class, 'adminCreate'])->name('knowledge.create');
            Route::post('/knowledge/manage', [KnowledgeBaseController::class, 'adminStore'])->name('knowledge.store');
            Route::get('/knowledge/manage/{article}/edit', [KnowledgeBaseController::class, 'adminEdit'])->name('knowledge.edit');
            Route::put('/knowledge/manage/{article}', [KnowledgeBaseController::class, 'adminUpdate'])->name('knowledge.update');
            Route::delete('/knowledge/manage/{article}', [KnowledgeBaseController::class, 'adminDestroy'])->name('knowledge.destroy');

            // Knowledge categories
            Route::resource('/knowledge/categories', KnowledgeCategoryController::class)->names('knowledge.categories');
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

        // Unified Activity Configuration page
        Route::get('/activity-configuration', [ActivityConfigurationController::class, 'index'])
            ->name('activity-configuration.index');

        // Activity Type & Sub-Activity Management
        // Legacy index redirects — point old index URLs to the unified page
        Route::get('activity-types', fn () => redirect()->route('admin.activity-configuration.index'))
            ->name('activity-types.index');
        Route::get('sub-activities', fn () => redirect()->route('admin.activity-configuration.index'))
            ->name('sub-activities.index');

        // Activity Types CRUD (excluding index, which is redirected above)
        Route::resource('activity-types', \App\Http\Controllers\Admin\ActivityTypeController::class)
            ->except(['index']);
        Route::post('activity-types/{activity_type}/assign-departments', [\App\Http\Controllers\Admin\ActivityTypeController::class, 'assignDepartments'])->name('activity-types.assign-departments');

        // Sub-Activities CRUD (excluding index, which is redirected above)
        Route::resource('sub-activities', \App\Http\Controllers\Admin\SubActivityController::class)
            ->except(['index']);
        Route::prefix('activity-admins')->name('activity-admins.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\ActivityAdminAssignmentController::class, 'index'])->name('index');
            Route::post('/{id}/toggle', [\App\Http\Controllers\Admin\ActivityAdminAssignmentController::class, 'toggle'])->name('toggle')->whereNumber('id');
            Route::post('/{id}/toggle-report', [\App\Http\Controllers\Admin\ActivityAdminAssignmentController::class, 'toggleReportAccess'])->name('toggle-report')->whereNumber('id');
        });

        Route::prefix('purchasing-admins')->name('purchasing-admins.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PurchasingAdminAssignmentController::class, 'index'])->name('index');
            Route::post('/{id}/toggle', [\App\Http\Controllers\Admin\PurchasingAdminAssignmentController::class, 'toggle'])->name('toggle')->whereNumber('id');
            Route::post('/{id}/toggle-report', [\App\Http\Controllers\Admin\PurchasingAdminAssignmentController::class, 'toggleReportAccess'])->name('toggle-report')->whereNumber('id');
        });

        Route::prefix('it-support-admins')->name('it-support-admins.')->group(function () {
            Route::get('/', [ITSupportAssignmentController::class, 'index'])->name('index');
            Route::post('/{id}/toggle', [ITSupportAssignmentController::class, 'toggle'])->name('toggle')->whereNumber('id');
            Route::post('/{id}/toggle-report', [ITSupportAssignmentController::class, 'toggleReportAccess'])->name('toggle-report')->whereNumber('id');
        });

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
            return redirect()
                ->route('admin.dashboard')
                ->with('info', 'Number sequence management page is not available yet.');
        })->name('number-sequences.index');

        // Workflow Management (placeholder routes)
        Route::get('/workflows', function () {
            return redirect()
                ->route('admin.dashboard')
                ->with('info', 'Workflow management page is not available yet.');
        })->name('workflows.index');
    });
});

require __DIR__.'/auth.php';
