<?php

namespace App\Livewire\Modules\PurchaseRequest;

use App\Models\Core\Department;
use App\Models\Modules\PurchaseRequest\PrItem;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use App\Services\Modules\PurchaseRequest\PurchaseRequestService;
use App\Services\Modules\PurchaseRequest\UniversalPRNumberingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Create extends Component
{
    // Livewire listeners
    protected $listeners = ['refreshComponent' => '$refresh'];

    // Form fields for complete PR
    public $business_unit_id = '';  // Selected business unit

    public $department_id = '';     // Selected department

    public $request_date = '';      // Request date

    public $description = '';       // Description

    public $approval_notes = '';    // Approval notes

    #[Validate('required|string|min:3|max:500')]
    public $purpose = '';           // Keperluan

    #[Validate('required|string|min:10|max:1000')]
    public $used_for = '';         // Digunakan untuk

    public $expected_date = '';    // Expected delivery date

    public $currency = 'IDR';      // Currency

    public $items = [];            // Items array (always initialized)

    public $departments = [];      // Available departments

    public $businessUnits = [];    // Available business units

    // Manual Approval Settings (simplified - no automatic flow)
    public $customApprovalList = [];        // Array of custom approval items with approver_id and task_type

    public $availableApprovers = [];        // Available approvers for selection - CRITICAL: Always array

    // Auto-generated fields (display only)
    public $submission_date = '';

    public $department_name = '';

    public $department_code = '';

    public $user_name = '';

    // State
    public $isLoading = false;

    public $totalAmount = 0;

    public $isEdit = false;

    public $isRejected = false;

    #[\Livewire\Attributes\Locked]
    public $purchaseRequestId = null;

    protected $existingPurchaseRequest = null;

    // Debug tracking properties
    private $debugLog = [];

    private $propertySetHistory = [];

    /**
     * Debug helper: Track when and where properties are set
     */
    private function debugTrackPropertySet(string $propertyName, $value, string $source, array $context = []): void
    {
        // Only enable in local environment to prevent sensitive data leakage
        if (!app()->environment('local')) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[1] ?? [];

        $entry = [
            'timestamp' => microtime(true),
            'property' => $propertyName,
            'value' => $value,
            'source' => $source,
            'caller_function' => $caller['function'] ?? 'unknown',
            'caller_line' => $caller['line'] ?? 0,
            'context' => $context,
        ];

        $this->propertySetHistory[] = $entry;

        // Log to Laravel log in debug mode
        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::debug("PR Create Property Set: {$propertyName}", $entry);
        }
    }

    /**
     * Debug helper: Log component lifecycle events
     */
    private function debugLog(string $event, array $data = []): void
    {
        // Only enable in local environment to prevent sensitive data leakage
        if (!app()->environment('local')) {
            return;
        }

        $entry = [
            'timestamp' => microtime(true),
            'event' => $event,
            'data' => $data,
            'memory' => memory_get_usage(true),
        ];

        $this->debugLog[] = $entry;

        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::debug("PR Create Lifecycle: {$event}", $entry);
        }
    }

    /**
     * Debug helper: Get property history for debugging
     */
    public function getPropertyHistory(?string $propertyName = null): array
    {
        if ($propertyName) {
            return array_filter($this->propertySetHistory, fn ($entry) => $entry['property'] === $propertyName);
        }

        return $this->propertySetHistory;
    }

    /**
     * Debug helper: Get full debug report
     */
    public function getDebugReport(): array
    {
        return [
            'current_properties' => [
                'user_name' => $this->user_name,
                'department_name' => $this->department_name,
                'department_code' => $this->department_code,
                'submission_date' => $this->submission_date,
            ],
            'property_history' => $this->propertySetHistory,
            'lifecycle_log' => $this->debugLog,
            'auth_status' => [
                'check' => Auth::check(),
                'id' => Auth::id(),
                'user_name' => Auth::user()?->name,
            ],
            'session' => [
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
            ],
        ];
    }

    /**
     * Initialize component properties properly using Livewire 3 conventions
     */
    public function mount(?PurchaseRequest $purchaseRequest = null, string $mode = 'create')
    {
        $this->debugLog('mount_start', [
            'mode' => $mode,
            'has_purchase_request' => $purchaseRequest !== null,
            'auth_check' => Auth::check(),
            'auth_id' => Auth::id(),
        ]);

        // CRITICAL: Initialize ALL properties FIRST before any logic (hosting fix)
        $this->items = [];
        $this->customApprovalList = [];
        $this->departments = [];
        $this->businessUnits = [];
        $this->availableApprovers = [];
        $this->currency = 'IDR';
        $this->isEdit = false;
        $this->isLoading = false;

        // Force session check before Auth check (hosting environment fix)
        if (session()->isStarted()) {
            session()->regenerate();
        }

        // Multi-layer authentication check for hosting environment
        if (! $this->ensureAuthenticated()) {
            $this->debugLog('mount_auth_failed', ['reason' => 'All authentication methods failed']);
            session()->flash('error', 'Please login to continue.');

            return redirect()->route('login');
        }

        // CRITICAL: Initialize user-dependent properties immediately
        $this->debugLog('before_initializeUserProperties');
        $this->initializeUserProperties();
        $this->debugLog('after_initializeUserProperties', [
            'user_name' => $this->user_name,
            'department_name' => $this->department_name,
            'department_code' => $this->department_code,
        ]);

        // Ensure proper initialization
        $this->ensurePropertiesInitialized();

        if ($mode === 'edit' && $purchaseRequest) {
            $this->isEdit = true;
            $this->purchaseRequestId = $purchaseRequest->id;
            $this->existingPurchaseRequest = $purchaseRequest->load(['items', 'businessUnit', 'department', 'user']);

            session([
                'current_business_unit_id' => $this->existingPurchaseRequest->business_unit_id,
                'current_department_id' => $this->existingPurchaseRequest->department_id,
            ]);

            $this->initializeEditState();
        } else {
            $this->initializeCreateState();
        }
    }

    /**
     * Livewire boot lifecycle - runs on EVERY request (hosting fix)
     * This ensures properties are always initialized even in strict hosting environments
     */
    public function boot()
    {
        $this->debugLog('boot_lifecycle', ['auth_check' => Auth::check()]);

        // CRITICAL: Force property initialization on EVERY request
        // This prevents "property not initialized" errors in hosting environments
        if (! is_array($this->items)) {
            $this->items = [];
        }
        if (! is_array($this->customApprovalList)) {
            $this->customApprovalList = [];
        }
        if (! is_array($this->availableApprovers)) {
            $this->availableApprovers = [];
        }
        if (! is_array($this->departments)) {
            $this->departments = [];
        }
        if (! is_array($this->businessUnits)) {
            $this->businessUnits = [];
        }

        // Ensure scalar properties have defaults
        $this->currency = $this->currency ?: 'IDR';
        $this->isEdit = $this->isEdit ?? false;
        $this->isLoading = $this->isLoading ?? false;

        // Re-authenticate if needed (hosting session issues)
        if (! Auth::check()) {
            $this->ensureAuthenticated();
        }
    }

    /**
     * Livewire hydrate lifecycle - runs after component is hydrated from request
     * This maintains state across Livewire requests in hosting environment
     */
    public function hydrate()
    {
        $this->debugLog('hydrate_lifecycle', [
            'auth_check' => Auth::check(),
            'items_count' => count($this->items ?? []),
        ]);

        // CRITICAL: Re-validate authentication after hydration
        // Hosting environments may lose auth context between requests
        if (! Auth::check()) {
            $this->ensureAuthenticated();
        }

        // Ensure user-dependent properties are still valid
        if (empty($this->user_name) || empty($this->department_name)) {
            try {
                $this->initializeUserProperties();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to re-initialize user properties in hydrate', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Re-ensure all properties are properly typed (hosting strict mode)
        $this->ensurePropertiesInitialized();
    }

    /**
     * Authentication check using standard Laravel methods only
     */
    private function ensureAuthenticated(): bool
    {
        // Method 1: Standard Auth check
        if (Auth::check()) {
            return true;
        }

        // Method 2: Try web guard explicitly
        if (Auth::guard('web')->check()) {
            Auth::setUser(Auth::guard('web')->user());

            return true;
        }

        // If authentication fails, user must login properly
        // REMOVED: Methods 3 & 4 (manual auth from session) - security vulnerability
        return false;
    }

    /**
     * Initialize user-dependent properties from authenticated user
     */
    private function initializeUserProperties(): void
    {
        try {
            // Get authenticated user - single source of truth
            $user = Auth::user();

            if (!$user) {
                throw new \Exception('User must be authenticated to access this page');
            }

            // Set user name immediately with validation
            $userName = $user->name ?: 'User #'.$user->id;
            $this->user_name = $userName;
            $this->debugTrackPropertySet('user_name', $userName, 'initializeUserProperties', [
                'user_id' => $user->id,
            ]);

            $this->submission_date = Carbon::today()->format('d/m/Y');
            $this->debugTrackPropertySet('submission_date', $this->submission_date, 'initializeUserProperties');

            // Enhanced department loading with multiple fallback strategies
            $departmentLoaded = false;

            // Strategy 1: Primary department
            if ($user->primaryDepartment) {
                $this->department_name = $user->primaryDepartment->name;
                $this->department_code = $user->primaryDepartment->code;
                $departmentLoaded = true;

                $this->debugTrackPropertySet('department_name', $this->department_name, 'initializeUserProperties:strategy1_primaryDepartment', [
                    'user_id' => $user->id,
                    'department_id' => $user->primaryDepartment->id,
                ]);
                $this->debugTrackPropertySet('department_code', $this->department_code, 'initializeUserProperties:strategy1_primaryDepartment');

                \Illuminate\Support\Facades\Log::info('Department loaded from primary department', [
                    'user_id' => $user->id,
                    'department_name' => $this->department_name,
                ]);
            }

            // Strategy 2: Try loading department by primary_department_id
            if (! $departmentLoaded && $user->primary_department_id) {
                $department = \App\Models\Core\Department::find($user->primary_department_id);
                if ($department) {
                    $this->department_name = $department->name;
                    $this->department_code = $department->code;
                    $departmentLoaded = true;

                    $this->debugTrackPropertySet('department_name', $this->department_name, 'initializeUserProperties:strategy2_primary_department_id', [
                        'department_id' => $user->primary_department_id,
                    ]);
                    $this->debugTrackPropertySet('department_code', $this->department_code, 'initializeUserProperties:strategy2_primary_department_id');

                    \Illuminate\Support\Facades\Log::info('Department loaded by primary_department_id', [
                        'user_id' => $user->id,
                        'department_id' => $user->primary_department_id,
                        'department_name' => $this->department_name,
                    ]);
                }
            }

            // Strategy 3: Try from session data
            if (! $departmentLoaded && session('current_department_id')) {
                $department = \App\Models\Core\Department::find(session('current_department_id'));
                if ($department) {
                    $this->department_name = $department->name;
                    $this->department_code = $department->code;
                    $departmentLoaded = true;

                    \Illuminate\Support\Facades\Log::info('Department loaded from session', [
                        'user_id' => $user->id,
                        'session_dept_id' => session('current_department_id'),
                        'department_name' => $this->department_name,
                    ]);
                }
            }

            // Strategy 4: Find first department where user has access
            if (! $departmentLoaded) {
                $userDepartments = \App\Models\Core\Department::whereHas('users', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->first();

                if ($userDepartments) {
                    $this->department_name = $userDepartments->name;
                    $this->department_code = $userDepartments->code;
                    $departmentLoaded = true;

                    \Illuminate\Support\Facades\Log::info('Department loaded from user relationships', [
                        'user_id' => $user->id,
                        'department_name' => $this->department_name,
                    ]);
                }
            }

            // Final fallback
            if (! $departmentLoaded) {
                $this->department_name = 'Department not set';
                $this->department_code = 'N/A';

                \Illuminate\Support\Facades\Log::warning('No department found for user', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'primary_department_id' => $user->primary_department_id ?? 'null',
                    'session_dept_id' => session('current_department_id') ?? 'null',
                ]);
            }

        } catch (\Exception $e) {
            // Enhanced fallback logging for debugging hosting issues
            \Illuminate\Support\Facades\Log::error('Critical authentication failure in PR create', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'session_data' => [
                    'auth_id' => session('auth.id'),
                    'current_user' => Auth::id(),
                    'request_user' => request()->user() ? request()->user()->id : null,
                ],
                'environment' => [
                    'app_env' => config('app.env'),
                    'session_driver' => config('session.driver'),
                    'auth_guards' => array_keys(config('auth.guards', [])),
                ],
            ]);

            // Set fallback values with error indication
            $this->user_name = 'Authentication Error - Please Refresh';
            $this->department_name = 'Authentication Error';
            $this->department_code = 'ERR';
            $this->submission_date = Carbon::today()->format('d/m/Y');
        }
    }

    /**
     * Ensure properties are properly initialized following Livewire 3 patterns
     * This is called after mount initialization for hosting environment compatibility
     */
    private function ensurePropertiesInitialized(): void
    {
        // Ensure all arrays are initialized (double-check for hosting)
        $this->items = is_array($this->items) ? $this->items : [];
        $this->customApprovalList = is_array($this->customApprovalList) ? $this->customApprovalList : [];
        $this->departments = is_array($this->departments) ? $this->departments : [];
        $this->businessUnits = is_array($this->businessUnits) ? $this->businessUnits : [];
        $this->availableApprovers = is_array($this->availableApprovers) ? $this->availableApprovers : [];

        // Ensure scalar properties have safe defaults
        $this->currency = $this->currency ?: 'IDR';
        $this->isEdit = (bool) $this->isEdit;
        $this->isLoading = (bool) $this->isLoading;
    }

    // Validation rules for complete PR
    protected $rules = [
        'business_unit_id' => 'required|exists:business_units,id',
        'department_id' => 'required|exists:departments,id',
        'request_date' => 'required|date',
        'description' => 'nullable|string|max:1000',
        'approval_notes' => 'nullable|string|max:1000',
        'purpose' => 'required|string|min:3|max:500',
        'used_for' => 'required|string|min:10|max:1000',
        'items' => 'required|array|min:1',
        'items.*.item_name' => 'required|string|max:255',
        'items.*.brand' => 'nullable|string|max:255',
        'items.*.description' => 'nullable|string|max:1000',
        'items.*.supplier_name' => 'nullable|string|max:255',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit' => 'required|string|max:50',
        'items.*.unit_price' => 'required|numeric|min:0',
    ];

    protected $messages = [
        'business_unit_id.required' => 'Business unit is required.',
        'department_id.required' => 'Department is required.',
        'request_date.required' => 'Request date is required.',
        'purpose.required' => 'Purpose field is required.',
        'purpose.min' => 'Purpose must be at least 3 characters.',
        'used_for.required' => 'Used for field is required.',
        'used_for.min' => 'Used for must be at least 10 characters.',
        'items.required' => 'At least one item is required.',
        'items.min' => 'At least one item is required.',
        'items.*.item_name.required' => 'Item name is required.',
        'items.*.quantity.required' => 'Quantity is required.',
        'items.*.quantity.min' => 'Quantity must be greater than 0.',
        'items.*.unit_price.required' => 'Unit price is required.',
        'items.*.unit_price.min' => 'Unit price must be 0 or greater.',
    ];

    protected function resolveExistingPurchaseRequest(): PurchaseRequest
    {
        if ($this->existingPurchaseRequest instanceof PurchaseRequest) {
            return $this->existingPurchaseRequest;
        }

        if (! $this->purchaseRequestId) {
            throw new \RuntimeException('Purchase request context is missing.');
        }

        $this->existingPurchaseRequest = PurchaseRequest::with(['items', 'businessUnit', 'department', 'user', 'approvals'])
            ->findOrFail($this->purchaseRequestId);

        return $this->existingPurchaseRequest;
    }

    protected function initializeCreateState(): void
    {
        // Ensure session data is available first
        $this->ensureSessionData();

        // Session-based initialization
        $this->business_unit_id = session('current_business_unit_id', '');
        $this->department_id = session('current_department_id', '');

        // Form field initialization
        $this->request_date = Carbon::today()->format('Y-m-d');
        $this->description = '';
        $this->approval_notes = '';
        $this->purpose = '';
        $this->used_for = '';
        $this->expected_date = null;
        $this->currency = 'IDR';

        // Workflow initialization
        // Initialize with empty approval list - user must select approvers
        $this->customApprovalList = [];
        $this->isEdit = false;

        // Display fields initialization
        $this->submission_date = Carbon::today()->format('d/m/Y');

        // Note: User and department info already set by initializeUserProperties()
        // No need to override here - keeps authentication data from mount()

        $this->loadBusinessUnits();
        $this->loadDepartments();
        $this->loadAvailableApprovers();

        $this->items = [];
        $this->addItem();
    }

    protected function initializeEditState(): void
    {
        $purchaseRequest = $this->resolveExistingPurchaseRequest();

        $this->business_unit_id = $purchaseRequest->business_unit_id;
        $this->department_id = $purchaseRequest->department_id;
        $this->request_date = optional($purchaseRequest->date_of_request)->format('Y-m-d') ?? Carbon::today()->format('Y-m-d');
        $this->description = $purchaseRequest->description ?? '';
        $this->approval_notes = $purchaseRequest->approval_notes ?? '';
        $this->purpose = $purchaseRequest->keperluan ?? '';
        $this->used_for = $purchaseRequest->used_for ?? '';
        $this->expected_date = optional($purchaseRequest->designated_date)->format('Y-m-d');
        $this->currency = $purchaseRequest->currency ?? 'IDR';
        
        // Track if PR is rejected for UI
        $this->isRejected = ($purchaseRequest->status === 'rejected');

        // Load existing approval workflow from approval_workflow JSON or from approvals table
        $workflowData = is_array($purchaseRequest->approval_workflow) 
            ? $purchaseRequest->approval_workflow 
            : json_decode($purchaseRequest->approval_workflow ?? '[]', true);

        // If approval_workflow is empty, load from pr_approvals table
        if (empty($workflowData) && $purchaseRequest->exists) {
            $workflowData = $purchaseRequest->approvals()
                ->orderBy('step_order')
                ->get()
                ->map(function ($approval) {
                    return [
                        'approver_id' => $approval->approver_id,
                        'approval_type' => $approval->approval_type,
                        'step_order' => $approval->step_order,
                    ];
                })
                ->toArray();
        }

        $this->customApprovalList = collect($workflowData)->map(function ($step) {
            return [
                'approver_id' => $step['approver_id'] ?? null,
                'task_type' => $step['approval_type'] ?? $step['task_type'] ?? 'approval',
                'amount_threshold' => $step['amount_threshold'] ?? null,
            ];
        })->toArray();

        $this->submission_date = optional($purchaseRequest->date_of_request)->format('d/m/Y') ?? Carbon::parse($purchaseRequest->created_at)->format('d/m/Y');
        $this->user_name = optional($purchaseRequest->user)->name ?? (Auth::user() ? Auth::user()->name : 'Unknown User');
        $this->department_name = optional($purchaseRequest->department)->name ?? 'Department not set';
        $this->department_code = optional($purchaseRequest->department)->code ?? 'N/A';

        $this->loadBusinessUnits();
        $this->loadDepartments();
        $this->loadAvailableApprovers();

        $this->items = $purchaseRequest->items->map(function ($item) use ($purchaseRequest) {
            return [
                'item_name' => $item->item_name,
                'brand_name' => $item->brand_name,
                'item_description' => $item->item_description,
                'supplier_name' => $item->supplier_name,
                'quantity' => (float) $item->quantity,
                'unit' => $item->unit,
                'unit_price' => (float) $item->unit_price,
                'currency' => $item->currency ?? $purchaseRequest->currency ?? 'IDR',
                'expense_department_id' => $item->expense_department_id ?? $purchaseRequest->department_id,
            ];
        })->toArray();

        if (empty($this->items)) {
            $this->addItem();
        }

        $this->calculateTotals();
    }

    public function updatedBusinessUnitId($value)
    {
        // Update departments when business unit changes
        $this->department_id = '';
        $this->loadDepartments();
    }

    public function loadBusinessUnits()
    {
        $user = Auth::user();

        if ($user && $user->isSuperAdmin()) {
            // Super admin can see all business units
            $this->businessUnits = \App\Models\Core\BusinessUnit::where('is_active', true)
                ->orderBy('name')
                ->get();
        } elseif ($user) {
            // Regular users see only accessible business units
            $accessibleBusinessUnitIds = $user->businessUnits()->pluck('business_unit_id')->toArray();
            $this->businessUnits = \App\Models\Core\BusinessUnit::whereIn('id', $accessibleBusinessUnitIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $this->businessUnits = collect();
        }
    }

    public function loadDepartments()
    {
        if ($this->business_unit_id) {
            $this->departments = Department::where('business_unit_id', $this->business_unit_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $this->departments = collect();
        }
    }

    /**
     * Get available approvers for selection - internal method
     */
    protected function fetchAvailableApprovers()
    {
        $currentUser = Auth::user();
        $currentBusinessUnitId = session('current_business_unit_id');

        if (! $currentUser || ! $currentBusinessUnitId) {
            return [];
        }

        // Get current business unit and its hierarchy
        $currentBU = \App\Models\Core\BusinessUnit::with(['parent', 'children'])->find($currentBusinessUnitId);
        $accessibleBusinessUnitIds = [$currentBusinessUnitId];

        // Include parent business unit for top management access
        if ($currentBU && $currentBU->parent) {
            $accessibleBusinessUnitIds[] = $currentBU->parent->id;
        }

        // Include child business units for broader management access
        if ($currentBU && $currentBU->children) {
            foreach ($currentBU->children as $child) {
                $accessibleBusinessUnitIds[] = $child->id;
            }
        }

        $users = \App\Models\Core\User::where(function ($query) use ($accessibleBusinessUnitIds) {
            // Users assigned to business units via UserBusinessUnit pivot
            $query->whereHas('businessUnits', function ($subQuery) use ($accessibleBusinessUnitIds) {
                $subQuery->whereIn('business_unit_id', $accessibleBusinessUnitIds)
                    ->where('is_active', true);
            })
            // OR users whose primary department belongs to accessible business units
                ->orWhereHas('primaryDepartment', function ($subQuery) use ($accessibleBusinessUnitIds) {
                    $subQuery->whereIn('business_unit_id', $accessibleBusinessUnitIds);
                });
        })
            ->where('is_active', true)
            ->where('id', '!=', $currentUser->id)
            ->with(['primaryDepartment'])
            ->orderBy('name')
            ->get();

        return $users->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'role' => ucfirst(str_replace('_', ' ', $user->global_role ?? 'staff')),
            'department' => optional($user->primaryDepartment)->name ?? 'No Department',
            'email' => $user->email,
        ])
            ->values()
            ->toArray();
    }

    /**
     * Load available approvers - populates the availableApprovers property with user-scoped caching
     */
    public function loadAvailableApprovers()
    {
        // Use Laravel Cache with user-scoped key to prevent data leakage between users
        $cacheKey = sprintf(
            'approvers:bu:%s:user:%s',
            session('current_business_unit_id', 0),
            Auth::id()
        );

        $this->availableApprovers = \Illuminate\Support\Facades\Cache::remember(
            $cacheKey,
            300, // 5 minutes
            fn() => $this->fetchAvailableApprovers()
        );
    }

    public function addItem()
    {
        // Ensure items array is properly initialized
        if (! is_array($this->items)) {
            $this->items = [];
        }

        $this->items[] = [
            'item_name' => '',
            'brand_name' => '',
            'item_description' => '',
            'supplier_name' => '',
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 0,
            'currency' => $this->currency,
        ];

        // Manual calculation trigger instead of automatic
        $this->refreshTotals();
    }

    public function removeItem($index)
    {
        if (is_array($this->items) && count($this->items) > 1 && isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->calculateTotals();
        }
    }

    // Optimized updater - only for quantity and unit_price that affect totals
    public function updatedItemsQuantity($value, $key)
    {
        // Only sanitize, don't auto-calculate to prevent loops
        $keyParts = explode('.', $key);
        if (count($keyParts) >= 2) {
            $index = $keyParts[0];
            $cleanValue = is_numeric($value) ? max(0, intval($value)) : 0;
            $this->items[$index]['quantity'] = $cleanValue;
        }
    }

    public function updatedItemsUnitPrice($value, $key)
    {
        // Only sanitize, don't auto-calculate to prevent loops
        $keyParts = explode('.', $key);
        if (count($keyParts) >= 2) {
            $index = $keyParts[0];
            $cleanValue = preg_replace('/[^0-9]/', '', $value);
            $cleanValue = is_numeric($cleanValue) ? max(0, intval($cleanValue)) : 0;
            $this->items[$index]['unit_price'] = $cleanValue;
        }
    }

    public function updatedCurrency()
    {
        // Update currency for all items when main currency changes
        foreach ($this->items as $index => $item) {
            $this->items[$index]['currency'] = $this->currency;
        }
        // No auto-calculation to prevent loops - user can manually trigger if needed
    }

    // Approval flow updater removed - only manual approval supported

    public function addCustomApproval()
    {
        $this->customApprovalList[] = [
            'approver_id' => '',
            'task_type' => 'approval',
        ];

        $this->dispatch('approval-row-added');
    }

    public function removeCustomApproval($index)
    {
        if (is_array($this->customApprovalList) && count($this->customApprovalList) > 1) {
            unset($this->customApprovalList[$index]);
            $this->customApprovalList = array_values($this->customApprovalList);
        }

        $this->dispatch('approval-row-removed');
    }

    protected function createCustomApprovalWorkflow(PurchaseRequest $purchaseRequest)
    {
        // Ensure customApprovalList is an array
        if (! is_array($this->customApprovalList)) {
            $this->customApprovalList = [];
        }

        // Validate that custom approvers are selected
        $validApprovals = array_filter($this->customApprovalList, function ($approval) {
            return ! empty($approval['approver_id']);
        });

        if (empty($validApprovals)) {
            throw new \Exception('Please select at least one approver for custom approval workflow.');
        }

        // Check for duplicate approvers
        $approverIds = array_column($validApprovals, 'approver_id');
        if (count($approverIds) !== count(array_unique($approverIds))) {
            throw new \Exception('Cannot select the same approver for multiple steps.');
        }

        // Create approval steps for custom workflow
        $stepOrder = 1;
        $workflowStructure = [];

        foreach ($validApprovals as $index => $approval) {
            $approverId = $approval['approver_id'];
            $taskType = $approval['task_type'] ?? 'approval';

            $approver = \App\Models\Core\User::find($approverId);

            if (! $approver) {
                throw new \Exception("Approver not found for step {$stepOrder}");
            }

            // Create approval record
            \App\Models\Modules\PurchaseRequest\PrApproval::create([
                'purchase_request_id' => $purchaseRequest->id,
                'approver_id' => $approverId,
                'step_order' => $stepOrder,
                'approval_type' => $taskType, // Use task_type (approval/paraf)
                'status' => 'pending',
                'assigned_at' => now(),
                'due_date' => $this->addBusinessDays(now(), 3), // Default 3 business days
                'notes' => null,
                'responded_at' => null,
            ]);

            // Build workflow structure
            $workflowStructure[] = [
                'approver_id' => $approverId,
                'approver_name' => $approver->name,
                'approver_email' => $approver->email,
                'step_order' => $stepOrder,
                'approval_type' => $taskType,
                'reason' => "Custom {$taskType} step {$stepOrder}",
                'due_date' => $this->addBusinessDays(now(), 3)->toISOString(),
            ];

            $stepOrder++;
        }

        // Update purchase request with workflow information
        $purchaseRequest->update([
            'approval_workflow' => $workflowStructure,
            'is_sequential_approval' => true,
            'status' => 'in_approval',
        ]);

        // Log the custom workflow creation
        \Illuminate\Support\Facades\Log::info('Custom approval workflow created', [
            'pr_number' => $purchaseRequest->pr_number,
            'total_steps' => count($validApprovals),
            'approvers' => collect($workflowStructure)->pluck('approver_name')->toArray(),
        ]);
    }

    protected function validateCustomApproval()
    {
        // Ensure customApprovalList is an array
        if (! is_array($this->customApprovalList)) {
            $this->customApprovalList = [];
        }

        $validApprovals = array_filter($this->customApprovalList, function ($approval) {
            return ! empty($approval['approver_id']);
        });

        if (empty($validApprovals)) {
            $this->addError('customApprovalList', 'Please select at least one approver for custom approval workflow.');
            throw new \Illuminate\Validation\ValidationException(validator([], []));
        }

        // Check for duplicate approvers
        $approverIds = array_column($validApprovals, 'approver_id');
        if (count($approverIds) !== count(array_unique($approverIds))) {
            $this->addError('customApprovalList', 'Cannot select the same approver for multiple steps.');
            throw new \Illuminate\Validation\ValidationException(validator([], []));
        }

        // Validate that all selected approvers exist and are active
        foreach ($validApprovals as $index => $approval) {
            $approverId = $approval['approver_id'];
            $approver = \App\Models\Core\User::where('id', $approverId)
                ->where('is_active', true)
                ->first();

            if (! $approver) {
                $this->addError("customApprovalList.{$index}.approver_id", 'Selected approver is not valid or inactive.');
                throw new \Illuminate\Validation\ValidationException(validator([], []));
            }
        }
    }

    /**
     * Calculate totals and clean item data with throttling to prevent loops
     */
    public function calculateTotals()
    {
        // Add a simple throttling mechanism
        static $lastCalculation = 0;
        $now = microtime(true);

        // Throttle to max 2 calculations per second
        if ($now - $lastCalculation < 0.5) {
            return;
        }
        $lastCalculation = $now;

        if (! is_array($this->items)) {
            $this->items = [];
        }

        // Clean and validate items data
        foreach ($this->items as $index => $item) {
            $this->items[$index]['quantity'] = intval($item['quantity'] ?? 0);
            $this->items[$index]['unit_price'] = intval(preg_replace('/[^0-9]/', '', $item['unit_price'] ?? 0));
        }

        // Total is now computed automatically via computed property
        $this->totalAmount = $this->grandTotal();
        $this->dispatch('totals-updated');
    }

    /**
     * Manual calculation method for user-triggered updates
     */
    public function refreshTotals()
    {
        $this->calculateTotals();
    }

    /**
     * Computed property for grand total - cached for performance
     */
    #[\Livewire\Attributes\Computed]
    public function grandTotal()
    {
        $total = 0;

        if (! is_array($this->items)) {
            return 0;
        }

        foreach ($this->items as $item) {
            $quantity = intval($item['quantity'] ?? 0);
            $unitPrice = intval(preg_replace('/[^0-9]/', '', $item['unit_price'] ?? 0));
            $total += $quantity * $unitPrice;
        }

        return $total;
    }

    /**
     * Legacy method for backward compatibility
     */
    public function getGrandTotal()
    {
        return $this->grandTotal();
    }

    /**
     * Livewire 3 property accessor - uses computed property
     */
    public function getTotalAmountProperty()
    {
        return $this->grandTotal();
    }

    /**
     * Check if current PR is rejected (for conditional button display)
     */
    public function getIsRejectedProperty(): bool
    {
        if (! $this->isEdit) {
            return false;
        }

        try {
            $pr = $this->resolveExistingPurchaseRequest();

            return $pr->status === 'rejected';
        } catch (\Exception $e) {
            return false;
        }
    }

    public function saveDraft()
    {
        $this->isLoading = true;

        if ($this->isEdit) {
            return $this->updateExistingDraft();
        }

        try {
            // Validate using Livewire 3 attributes (validation will be automatic)
            $this->validate();

            DB::beginTransaction();

            // Generate PR number with current date
            $currentDate = Carbon::today();
            $numberingService = app(UniversalPRNumberingService::class);
            $result = $numberingService->generatePRNumber(
                Auth::user(),
                session('current_business_unit_id'),
                null,
                $currentDate
            );

            // Create purchase request as draft
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $result['formatted_number'],
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $result['sequence_id'],
                'keperluan' => $this->purpose,
                'used_for' => $this->used_for,
                'date_of_request' => $currentDate->format('Y-m-d'),
                'designated_date' => $this->expected_date ? Carbon::parse($this->expected_date)->format('Y-m-d') : null,
                'status' => 'draft',
                'currency' => $this->currency,
                'last_modified_by' => Auth::id(),
            ]);

            // Create PR items if any
            foreach ($this->items as $index => $itemData) {
                if (! empty($itemData['item_name'])) {
                    PrItem::create([
                        'purchase_request_id' => $purchaseRequest->id,
                        'item_order' => $index + 1,
                        'item_name' => $itemData['item_name'],
                        'brand_name' => $itemData['brand_name'],
                        'item_description' => $itemData['item_description'],
                        'supplier_name' => $itemData['supplier_name'],
                        'quantity' => $itemData['quantity'] ?? 1,
                        'unit' => $itemData['unit'] ?? 'pcs',
                        'unit_price' => str_replace(',', '', $itemData['unit_price'] ?? 0),
                        'currency' => $this->currency,
                        'expense_department_id' => session('current_department_id'),
                    ]);
                }
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            DB::commit();

            session()->flash('success', "Purchase Request {$result['formatted_number']} has been saved as draft.");

            return redirect()->route('purchase-requests.show', $purchaseRequest);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to save draft: '.$e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    protected function buildRequestPayload(string $status = 'draft'): array
    {
        $items = collect($this->items)->map(function ($item) {
            return [
                'item_name' => $item['item_name'] ?? '',
                'brand_name' => $item['brand_name'] ?? null,
                'item_description' => $item['item_description'] ?? null,
                'supplier_name' => $item['supplier_name'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'unit' => $item['unit'] ?? 'pcs',
                'unit_price' => is_numeric($item['unit_price'] ?? null) ? $item['unit_price'] : str_replace(',', '', $item['unit_price'] ?? 0),
                'currency' => $item['currency'] ?? $this->currency,
                'expense_department_id' => $item['expense_department_id'] ?? $this->department_id,
            ];
        })->toArray();

        if (empty($items)) {
            $items[] = [
                'item_name' => '',
                'brand_name' => null,
                'item_description' => null,
                'supplier_name' => null,
                'quantity' => 1,
                'unit' => 'pcs',
                'unit_price' => 0,
                'currency' => $this->currency,
                'expense_department_id' => $this->department_id,
            ];
        }

        return [
            'keperluan' => $this->purpose,
            'used_for' => $this->used_for,
            'date_of_request' => $this->request_date ?? Carbon::today()->format('Y-m-d'),
            'designated_date' => $this->expected_date ? Carbon::parse($this->expected_date)->format('Y-m-d') : null,
            'status' => $status,
            'currency' => $this->currency,
            'items' => $items,
        ];
    }

    protected function updateExistingDraft()
    {
        $this->ensureSessionData();

        try {
            $this->validate([
                'purpose' => 'required|string|min:3|max:500',
                'used_for' => 'required|string|min:10|max:1000',
            ]);

            DB::beginTransaction();

            $data = $this->buildRequestPayload('draft');
            $service = app(\App\Services\Modules\PurchaseRequest\PurchaseRequestService::class);
            $purchaseRequest = $service->updatePurchaseRequest($this->resolveExistingPurchaseRequest()->fresh(['items', 'approvals']), $data);

            $this->existingPurchaseRequest = $purchaseRequest;

            $purchaseRequest->update([
                'status' => 'draft',
                'submitted_at' => null,
                'approved_at' => null,
                'rejected_at' => null,
                'voided_at' => null,
                'last_modified_by' => Auth::id(),
            ]);

            DB::commit();

            session()->flash('success', 'Draft updated successfully.');

            return redirect()->route('purchase-requests.show', $purchaseRequest);

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to update draft: '.$e->getMessage());
        } finally {
            $this->isLoading = false;
        }

        return null;
    }

    public function submitRequest()
    {
        return $this->submitPurchaseRequest();
    }

    public function submitPurchaseRequest()
    {
        $this->isLoading = true;

        if ($this->isEdit) {
            return $this->submitUpdatedRequest();
        }

        try {
            // Initialize session data from authenticated user if not exists
            $this->ensureSessionData();

            // Check session data
            if (! session('current_business_unit_id') || ! session('current_department_id')) {
                $this->dispatch('notify',
                    message: 'Session expired. Please refresh the page and try again.',
                    type: 'error'
                );
                throw new \Exception('Missing business unit or department session data. Please refresh the page and try again.');
            }

            // Validate the complete form with enhanced error reporting
            $this->validateForm();

            // Validate custom approval (required for all requests)
            $this->validateCustomApproval();

            DB::beginTransaction();

            // Generate PR number with current date
            $currentDate = Carbon::today();
            $numberingService = app(UniversalPRNumberingService::class);
            $result = $numberingService->generatePRNumber(
                Auth::user(),
                session('current_business_unit_id'),
                null,
                $currentDate
            );

            // Create purchase request
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => $result['formatted_number'],
                'business_unit_id' => session('current_business_unit_id'),
                'department_id' => session('current_department_id'),
                'user_id' => Auth::id(),
                'sequence_id' => $result['sequence_id'],
                'keperluan' => $this->purpose,
                'used_for' => $this->used_for,
                'date_of_request' => $currentDate->format('Y-m-d'),
                'designated_date' => $this->expected_date ? Carbon::parse($this->expected_date)->format('Y-m-d') : null,
                'status' => 'submitted',
                'submitted_at' => now(),
                'currency' => $this->currency,
                'last_modified_by' => Auth::id(),
            ]);

            // Create PR items
            foreach ($this->items as $index => $itemData) {
                PrItem::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'item_order' => $index + 1,
                    'item_name' => $itemData['item_name'],
                    'brand_name' => $itemData['brand_name'],
                    'item_description' => $itemData['item_description'],
                    'supplier_name' => $itemData['supplier_name'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'unit_price' => str_replace(',', '', $itemData['unit_price']),
                    'currency' => $this->currency,
                    'expense_department_id' => session('current_department_id'),
                ]);
            }

            // Update total amount
            $purchaseRequest->updateTotalAmount();

            // Create manual approval workflow (required for all requests)
            $this->createCustomApprovalWorkflow($purchaseRequest);

            DB::commit();

            // ✅ Clear dashboard cache for affected users
            app(PurchaseRequestService::class)->clearDashboardCache($purchaseRequest);

            session()->flash('success', "Purchase Request {$result['formatted_number']} has been submitted for approval.");

            return redirect()->route('purchase-requests.show', $purchaseRequest);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            // Consolidate validation errors into single toast
            $errors = $e->validator->errors();
            $totalErrors = $errors->count();

            if ($totalErrors > 1) {
                // Create consolidated error message
                $errorList = [];
                foreach ($errors->all() as $error) {
                    $errorList[] = '• '.e($error);
                }
                $consolidatedMessage = "Found {$totalErrors} validation errors:\n".implode("\n", array_slice($errorList, 0, 5));

                if ($totalErrors > 5) {
                    $consolidatedMessage .= "\n• ... and ".($totalErrors - 5).' more errors';
                }

                $this->dispatch('notify',
                    message: $consolidatedMessage,
                    type: 'error',
                    duration: 10000
                );
            } else {
                // Single error message - escape HTML
                $this->dispatch('notify',
                    message: 'Validation Error: '.e($errors->first()),
                    type: 'error',
                    duration: 8000
                );
            }

            // Re-throw to show field-specific errors
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('notify',
                message: 'Failed to submit: '.$e->getMessage(),
                type: 'error',
                duration: 8000
            );

            session()->flash('error', 'Failed to submit purchase request: '.$e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    protected function submitUpdatedRequest()
    {
        $this->ensureSessionData();

        try {
            if (! session('current_business_unit_id') || ! session('current_department_id')) {
                $this->dispatch('notify',
                    message: 'Session expired. Please refresh the page and try again.',
                    type: 'error'
                );
                throw new \Exception('Missing business unit or department session data. Please refresh the page and try again.');
            }

            $this->validateForm();

            // Validate custom approval (required for all requests)
            $this->validateCustomApproval();

            DB::beginTransaction();

            $existingPR = $this->resolveExistingPurchaseRequest()->fresh(['items', 'approvals']);
            $wasRejected = $existingPR->status === 'rejected';

            // Keep rejected status if PR was rejected (don't auto-resubmit)
            $targetStatus = $wasRejected ? 'rejected' : 'submitted';
            $data = $this->buildRequestPayload($targetStatus);

            $service = app(\App\Services\Modules\PurchaseRequest\PurchaseRequestService::class);
            $purchaseRequest = $service->updatePurchaseRequest($existingPR, $data);

            $this->existingPurchaseRequest = $purchaseRequest;

            // Only update timestamps and workflow if NOT rejected
            if (! $wasRejected) {
                $purchaseRequest->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'last_modified_by' => Auth::id(),
                ]);

                // Create manual approval workflow only for non-rejected
                $this->createCustomApprovalWorkflow($purchaseRequest);
            } else {
                // For rejected PR, just update last_modified_by
                $purchaseRequest->update([
                    'last_modified_by' => Auth::id(),
                ]);
            }

            DB::commit();

            // ✅ Clear dashboard cache for affected users
            app(PurchaseRequestService::class)->clearDashboardCache($purchaseRequest);

            if ($wasRejected) {
                session()->flash('success', 'Purchase Request has been updated. Use "Resubmit for Approval" button to resubmit.');
            } else {
                session()->flash('success', 'Purchase Request has been updated and submitted for approval.');
            }

            return redirect()->route('purchase-requests.show', $purchaseRequest);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('notify',
                message: 'Failed to submit: '.$e->getMessage(),
                type: 'error',
                duration: 8000
            );

            session()->flash('error', 'Failed to submit purchase request: '.$e->getMessage());
        } finally {
            $this->isLoading = false;
        }

        return null;
    }

    protected function addBusinessDays(Carbon $date, int $days): Carbon
    {
        $result = $date->copy();

        while ($days > 0) {
            $result->addDay();

            // Skip weekends
            if ($result->isWeekday()) {
                $days--;
            }
        }

        return $result;
    }

    /**
     * Ensure session data is available from authenticated user with robust error handling
     */
    private function ensureSessionData()
    {
        try {
            if (! session('current_business_unit_id') || ! session('current_department_id')) {
                $user = Auth::user();

                if (! $user) {
                    throw new \Exception('User not authenticated in ensureSessionData');
                }

                // Get user's primary business unit and department with error handling
                if ($user->primaryDepartment && $user->primaryDepartment->businessUnit) {
                    session([
                        'current_business_unit_id' => $user->primaryDepartment->businessUnit->id,
                        'current_business_unit_code' => $user->primaryDepartment->businessUnit->code,
                        'current_business_unit_name' => $user->primaryDepartment->businessUnit->name,
                        'current_department_id' => $user->primaryDepartment->id,
                        'current_user_role' => $user->global_role,
                    ]);

                    \Illuminate\Support\Facades\Log::info('Session data initialized from user primary department', [
                        'user_id' => $user->id,
                        'business_unit_id' => $user->primaryDepartment->businessUnit->id,
                        'department_id' => $user->primaryDepartment->id,
                    ]);

                } elseif ($user->global_role === 'super_admin') {
                    // For super admin, use WG business unit as fallback
                    $wgBusinessUnit = \App\Models\Core\BusinessUnit::where('code', 'WG')->first();
                    if ($wgBusinessUnit) {
                        $corporateDept = \App\Models\Core\Department::where('business_unit_id', $wgBusinessUnit->id)
                            ->where('code', 'CORP')
                            ->first();

                        session([
                            'current_business_unit_id' => $wgBusinessUnit->id,
                            'current_business_unit_code' => $wgBusinessUnit->code,
                            'current_business_unit_name' => $wgBusinessUnit->name,
                            'current_department_id' => $corporateDept ? $corporateDept->id : null,
                            'current_user_role' => 'super_admin',
                        ]);

                        \Illuminate\Support\Facades\Log::info('Session data initialized for super admin', [
                            'user_id' => $user->id,
                            'business_unit_id' => $wgBusinessUnit->id,
                        ]);
                    }
                } else {
                    // User without proper department setup
                    \Illuminate\Support\Facades\Log::warning('User without proper department setup accessing PR', [
                        'user_id' => $user->id,
                        'user_email' => $user->email,
                        'primary_department_id' => $user->primary_department_id,
                    ]);

                    throw new \Exception('User does not have proper department setup');
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to ensure session data', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Set minimal session data to prevent further errors
            if (! session('current_business_unit_id')) {
                session(['current_business_unit_id' => 1]); // Default to first BU
            }
            if (! session('current_department_id')) {
                session(['current_department_id' => 1]); // Default to first dept
            }
        }
    }

    /**
     * Enhanced validation with toast notifications
     */
    public function validateForm()
    {
        try {
            $this->validate();

            // If validation passes, show success message
            $this->dispatch('notify',
                message: 'Form validation passed!',
                type: 'success',
                duration: 3000
            );

            return true;

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Get all validation errors
            $errors = $e->validator->errors();
            $totalErrors = $errors->count();

            if ($totalErrors > 1) {
                // Create consolidated error message - escape HTML to prevent XSS
                $errorList = [];
                foreach ($errors->all() as $error) {
                    $errorList[] = '• '.e($error);
                }
                $consolidatedMessage = "Found {$totalErrors} validation errors:\n".implode("\n", array_slice($errorList, 0, 5));

                if ($totalErrors > 5) {
                    $consolidatedMessage .= "\n• ... and ".($totalErrors - 5).' more errors';
                }

                $this->dispatch('notify',
                    message: $consolidatedMessage,
                    type: 'error',
                    duration: 12000
                );
            } else {
                // Show specific error for single issue - escape HTML
                $this->dispatch('notify',
                    message: 'Validation Error: '.e($errors->first()),
                    type: 'error',
                    duration: 8000
                );
            }

            // Re-throw to show field-specific errors
            throw $e;
        }
    }

    /**
     * Validate specific field and show toast if error
     */
    public function validateField($field)
    {
        try {
            $this->validateOnly($field);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $error = $e->validator->errors()->first($field);
            if ($error) {
                $this->dispatch('notify',
                    message: $error,
                    type: 'error',
                    duration: 5000
                );
            }
        }
    }

    /**
     * Force refresh user properties from current authentication - for hosting environment
     */
    /**
     * Get available approvers using computed property pattern for Blade access
     */
    #[\Livewire\Attributes\Computed]
    public function getAvailableApproversProperty()
    {
        // Ensure property is always initialized as array
        if (! is_array($this->availableApprovers) || empty($this->availableApprovers)) {
            $this->loadAvailableApprovers();
        }

        return is_array($this->availableApprovers) ? $this->availableApprovers : [];
    }

    public function render()
    {
        try {
            // Simple authentication check
            if (! Auth::check()) {
                \Illuminate\Support\Facades\Log::warning('Unauthenticated user accessing PR create component');

                return redirect()->route('login');
            }

            // Note: User properties already set by mount() -> initializeUserProperties()
            // No need to re-initialize or refresh here - trust the mount() data

            // Ensure session data is available
            if (! session('current_business_unit_id') || ! session('current_department_id')) {
                $this->ensureSessionData();
            }

            // CRITICAL: Ensure all properties are always initialized
            if (! is_array($this->availableApprovers)) {
                $this->availableApprovers = [];
                try {
                    $this->loadAvailableApprovers();
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to load available approvers in render', [
                        'error' => $e->getMessage(),
                    ]);
                    $this->availableApprovers = [];
                }
            }

            // Ensure items array is properly initialized
            if (! is_array($this->items)) {
                $this->items = [
                    [
                        'item_name' => '',
                        'brand_name' => '',
                        'item_description' => '',
                        'supplier_name' => '',
                        'quantity' => 1,
                        'unit' => 'pcs',
                        'unit_price' => 0,
                        'currency' => $this->currency ?? 'IDR',
                    ],
                ];
            }

            // Ensure critical boolean properties have safe defaults
            $this->isEdit = $this->isEdit ?? false;
            $this->isLoading = $this->isLoading ?? false;

            // Use computed property for reliable access in Blade
            $availableApprovers = $this->getAvailableApproversProperty();

            // Reduced logging - only log on first render or errors
            static $renderCount = 0;
            if ($renderCount++ === 0) {
                \Illuminate\Support\Facades\Log::info('PR Create component first render completed', [
                    'user_name' => $this->user_name ?? 'NULL',
                    'items_count' => count($this->items ?? []),
                ]);
            }

            // Note: All public properties are automatically available in the view
            // No need to pass them explicitly - Livewire handles this
            return view('livewire.modules.purchase-request.create', [
                'availableApprovers' => $availableApprovers, // Computed property
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Critical error in render method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            // Fallback render with minimal data
            return view('livewire.modules.purchase-request.create', [
                'isEdit' => false,
                'isLoading' => false,
                'items' => [],
                'customApprovalList' => [],
                'availableApprovers' => [],
                'totalAmount' => 0,
                'currency' => 'IDR',
                'user_name' => 'System Error - Please Refresh',
                'department_name' => 'System Error',
                'department_code' => 'ERR',
                'submission_date' => \Carbon\Carbon::today()->format('d/m/Y'),
            ]);
        }
    }

    /**
     * Save changes and resubmit for approval (for rejected PRs)
     * This combines update + workflow reset + resubmit in one action
     */
    public function saveAndResubmit()
    {
        $this->isLoading = true;

        try {
            // Must be in edit mode
            if (! $this->isEdit) {
                $this->dispatch('notify',
                    message: 'This action is only available in edit mode.',
                    type: 'error',
                    duration: 5000
                );
                throw new \Exception('This action is only available in edit mode.');
            }

            // Get the existing PR
            $purchaseRequest = $this->resolveExistingPurchaseRequest();

            // Only allow for rejected PRs (STRICT CHECK)
            if ($purchaseRequest->status !== 'rejected') {
                $this->dispatch('notify',
                    message: 'Only rejected purchase requests can be resubmitted. Current status: '.$purchaseRequest->status,
                    type: 'error',
                    duration: 5000
                );
                throw new \Exception('Only rejected purchase requests can be resubmitted.');
            }

            // Validate ownership
            if ($purchaseRequest->user_id !== Auth::id()) {
                $this->dispatch('notify',
                    message: 'You are not authorized to resubmit this purchase request.',
                    type: 'error',
                    duration: 5000
                );
                throw new \Exception('You are not authorized to resubmit this purchase request.');
            }

            // Validate form
            $this->validateForm();

            // Validate custom approval (required for all requests)
            $this->validateCustomApproval();

            DB::beginTransaction();

            // Step 1: Update the PR with new data (but keep status as rejected temporarily)
            $data = $this->buildRequestPayload('rejected'); // Keep rejected first
            $service = app(\App\Services\Modules\PurchaseRequest\PurchaseRequestService::class);
            $purchaseRequest = $service->updatePurchaseRequest($purchaseRequest->fresh(['items', 'approvals']), $data);

            // Step 2: Reset workflow (delete old approvals)
            $originalSubmittedAt = $purchaseRequest->submitted_at; // PRESERVE for QR token reusability
            $workflowService = app(\App\Services\Modules\PurchaseRequest\ApprovalWorkflowService::class);
            $workflowService->resetWorkflow($purchaseRequest);

            // Step 3: Update PR status to submitted (PRESERVE submitted_at for QR token)
            $purchaseRequest->update([
                'status' => 'submitted',
                'submitted_at' => $originalSubmittedAt ?? now(), // PRESERVE original timestamp
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'last_modified_by' => Auth::id(),
            ]);

            // Step 4: Create NEW approval workflow
            $this->createCustomApprovalWorkflow($purchaseRequest);

            DB::commit();

            session()->flash('success', "Purchase Request {$purchaseRequest->pr_number} has been updated and resubmitted for approval. Approval workflow has been reset.");

            return redirect()->route('purchase-requests.show', $purchaseRequest);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            // Consolidate validation errors
            $errors = $e->validator->errors();
            $totalErrors = $errors->count();

            if ($totalErrors > 1) {
                // Escape HTML to prevent XSS
                $errorList = [];
                foreach ($errors->all() as $error) {
                    $errorList[] = '• '.e($error);
                }
                $consolidatedMessage = "Found {$totalErrors} validation errors:\n".implode("\n", array_slice($errorList, 0, 5));

                if ($totalErrors > 5) {
                    $consolidatedMessage .= "\n• ... and ".($totalErrors - 5).' more errors';
                }

                $this->dispatch('notify',
                    message: $consolidatedMessage,
                    type: 'error',
                    duration: 10000
                );
            } else {
                $this->dispatch('notify',
                    message: 'Validation Error: '.e($errors->first()),
                    type: 'error',
                    duration: 8000
                );
            }

            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->dispatch('notify',
                message: 'Failed to resubmit: '.$e->getMessage(),
                type: 'error',
                duration: 8000
            );

            session()->flash('error', 'Failed to resubmit purchase request: '.$e->getMessage());
        } finally {
            $this->isLoading = false;
        }

        return null;
    }
}
