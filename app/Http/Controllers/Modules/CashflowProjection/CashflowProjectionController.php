<?php

namespace App\Http\Controllers\Modules\CashflowProjection;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashflowProjection\CashflowProjectionDashboardFilterRequest;
use App\Http\Requests\CashflowProjection\ImportCashflowProjectionEntriesRequest;
use App\Http\Requests\CashflowProjection\StoreCashflowProjectionLineItemRequest;
use App\Http\Requests\CashflowProjection\UpdateCashflowProjectionLineItemRequest;
use App\Http\Requests\CashflowProjection\UpsertCashflowProjectionFinanceInputRequest;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionAuditLog;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Models\Modules\CashflowProjection\CashflowProjectionLinkedUnit;
use App\Services\Modules\CashflowProjection\CashflowProjectionAccessService;
use App\Services\Modules\CashflowProjection\CashflowProjectionAuditService;
use App\Services\Modules\CashflowProjection\CashflowProjectionEntryImportService;
use App\Services\Modules\CashflowProjection\CashflowProjectionEntryImportTemplateService;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopeService;
use App\Services\Modules\CashflowProjection\CashflowProjectionTemplateService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashflowProjectionController extends Controller
{
    /**
     * Global minimum cash balance threshold (IDR).
     *
     * Configured via `config('features.cashflow.minimum_balance_global')`
     * (env: CASHFLOW_MINIMUM_BALANCE_GLOBAL). Default: 200_000_000.
     */
    public function __construct(
        protected CashflowProjectionAccessService $accessService,
        protected CashflowProjectionTemplateService $templateService,
        protected CashflowProjectionScopeService $scopeService,
        protected CashflowProjectionAuditService $auditService,
        protected CashflowProjectionEntryImportTemplateService $entryImportTemplateService,
        protected CashflowProjectionEntryImportService $entryImportService
    ) {}

    public function index(CashflowProjectionDashboardFilterRequest $request): Response|\Symfony\Component\HttpFoundation\Response
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canAccess($user, $businessUnitId), 403);

        // Dashboard is finance-only; non-finance users should use Entries page
        if (! $this->accessService->isFinanceUser($user, $businessUnitId)) {
            return Inertia::location(route('cashflow-projection.entries'));
        }

        $dashboardFilters = $this->resolveDashboardFilters($request, $businessUnitId);
        $year = $dashboardFilters['year'];
        $selectedMonth = $dashboardFilters['month'];
        $cycle = $this->findOrCreateCycle($businessUnitId, $year, $user->id);

        $canManageFinance = $this->userHasFinanceAssignment($user, $businessUnitId);

        // Finance users see ALL departments in their BU; others see only their own
        if ($canManageFinance) {
            $departments = Department::query()
                ->with('businessUnit')
                ->where('business_unit_id', $businessUnitId)
                ->where('is_active', true)
                ->get();
        } else {
            $assignments = $user->activeBusinessUnits()
                ->with(['department.businessUnit', 'position'])
                ->where('business_unit_id', $businessUnitId)
                ->get();

            $departments = $assignments
                ->pluck('department')
                ->filter(fn ($department) => $department instanceof Department && $department->is_active)
                ->unique('id')
                ->values();
        }

        // For finance users, also load departments from linked BUs
        $linkedBuIds = $canManageFinance ? $this->getLinkedBusinessUnitIds($businessUnitId) : [];
        if ($canManageFinance && count($linkedBuIds) > 0) {
            $linkedDepartments = Department::query()
                ->with('businessUnit')
                ->whereIn('business_unit_id', $linkedBuIds)
                ->where('is_active', true)
                ->get();
            $departments = $departments->merge($linkedDepartments)->unique('id')->values();
        }

        $departmentIds = $departments->pluck('id');

        $lineItems = CashflowProjectionLineItem::query()
            ->with('department')
            ->where('cycle_id', $cycle->id)
            ->whereIn('department_id', $departmentIds)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $financeInputs = $canManageFinance
            ? CashflowProjectionFinanceInput::query()
                ->where('cycle_id', $cycle->id)
                ->orderBy('month')
                ->get()
            : collect();

        // Linked BU support: finance users can view consolidated data
        $scope = count($linkedBuIds) > 0
            ? (string) $request->string('scope', 'consolidated')
            : 'own';

        $allLineItems = $lineItems;

        if ($scope === 'consolidated' && count($linkedBuIds) > 0) {
            $linkedCycles = $this->getLinkedCycles($linkedBuIds, $year, $user->id);
            $linkedLineItems = $this->getLinkedLineItems($linkedCycles);
            $allLineItems = $lineItems->merge($linkedLineItems)
                ->sortByDesc('transaction_date')
                ->sortByDesc('id')
                ->values();

            // Also include finance inputs from linked cycles
            $linkedFinanceInputIds = $linkedCycles->pluck('id');
            $linkedFinanceInputs = CashflowProjectionFinanceInput::query()
                ->whereIn('cycle_id', $linkedFinanceInputIds)
                ->orderBy('month')
                ->get();

            // Merge finance inputs by month (sum values)
            foreach ($linkedFinanceInputs as $linkedInput) {
                $existing = $financeInputs->firstWhere('month', $linkedInput->month);
                if ($existing) {
                    $existing->cash_on_hand = (float) $existing->cash_on_hand + (float) $linkedInput->cash_on_hand;
                    $existing->receivable_estimate = (float) $existing->receivable_estimate + (float) $linkedInput->receivable_estimate;
                    $existing->upcoming_event_revenue_estimate = (float) $existing->upcoming_event_revenue_estimate + (float) $linkedInput->upcoming_event_revenue_estimate;
                    $existing->capital_injection_estimate = (float) $existing->capital_injection_estimate + (float) $linkedInput->capital_injection_estimate;
                    $existing->other_income = (float) $existing->other_income + (float) $linkedInput->other_income;
                } else {
                    $financeInputs->push($linkedInput);
                }
            }

            $financeInputs = $financeInputs->sortBy('month')->values();
        }

        // Build linked BU info for frontend
        $linkedBusinessUnits = [];
        if (count($linkedBuIds) > 0) {
            $linkedBusinessUnits = \App\Models\Core\BusinessUnit::query()
                ->whereIn('id', $linkedBuIds)
                ->where('is_active', true)
                ->get()
                ->map(fn (\App\Models\Core\BusinessUnit $bu) => [
                    'id' => $bu->id,
                    'code' => $bu->code,
                    'name' => $bu->name,
                ])
                ->values()
                ->all();
        }

        $filteredLineItems = $this->filterLineItemsByPeriod(
            $allLineItems,
            $dashboardFilters['start'],
            $dashboardFilters['end']
        );

        $dailySummary = $this->buildPeriodDailySummary(
            $filteredLineItems,
            $dashboardFilters['start'],
            $dashboardFilters['end']
        );
        $monthlySummary = $this->buildMonthlySummary($allLineItems, $financeInputs);
        $summary = $this->buildDashboardSummary(
            $filteredLineItems,
            $financeInputs,
            $monthlySummary,
            $dashboardFilters['start'],
            $dashboardFilters['end']
        );

        return Inertia::render('CashflowProjection/Index', [
            'year' => $year,
            'selectedMonth' => $selectedMonth,
            'cycle' => [
                'id' => $cycle->id,
                'status' => $cycle->status,
                'year' => $cycle->year,
            ],
            'minimumBalanceGlobal' => (int) config('features.cashflow.minimum_balance_global', 200000000),
            'filters' => [
                'mode' => $dashboardFilters['mode'],
                'year' => $year,
                'month' => $selectedMonth,
                'start_date' => $dashboardFilters['start']->format('Y-m-d'),
                'end_date' => $dashboardFilters['end']->format('Y-m-d'),
                'available_years' => $dashboardFilters['available_years'],
            ],
            'summary' => $summary,
            'dailySummary' => $dailySummary,
            'monthlySummary' => $monthlySummary,
            'departments' => $departments->map(function (Department $department) {
                return [
                    'id' => $department->id,
                    'code' => $department->code,
                    'name' => $department->name,
                    'business_unit_id' => $department->business_unit_id,
                    'business_unit_code' => $department->businessUnit?->code,
                    'template_type' => $this->templateService->templateTypeForDepartment($department),
                    'actions' => $this->templateService->actionOptionsForDepartment($department),
                ];
            })->values(),
            'lineItems' => $filteredLineItems->map(function (CashflowProjectionLineItem $item) {
                $meta = $this->templateService->metaForActionCode($item->action_code, $item->department);

                return [
                    'id' => $item->id,
                    'department_id' => $item->department_id,
                    'department_code' => $item->department?->code,
                    'department_name' => $item->department?->name,
                    'business_unit_code' => $item->department?->businessUnit?->code,
                    'flow_type' => $item->flow_type,
                    'action_code' => $item->action_code,
                    'action_label' => $this->templateService->displayLabelForAction($item->action_code, $item->department) ?? $meta['label'] ?? $item->action_code,
                    'transaction_date' => optional($item->transaction_date)->format('Y-m-d'),
                    'due_date' => optional($item->due_date)->format('Y-m-d'),
                    'amount' => (float) $item->amount,
                    'description' => $item->description,
                    'notes' => $item->notes,
                    'is_estimated_date' => (bool) $item->is_estimated_date,
                ];
            })->values(),
            'financeInputs' => $financeInputs->map(function (CashflowProjectionFinanceInput $input) {
                return [
                    'id' => $input->id,
                    'month' => $input->month,
                    'cash_on_hand' => (float) $input->cash_on_hand,
                    'receivable_estimate' => (float) $input->receivable_estimate,
                    'upcoming_event_revenue_estimate' => (float) $input->upcoming_event_revenue_estimate,
                    'capital_injection_estimate' => (float) $input->capital_injection_estimate,
                    'other_income' => (float) $input->other_income,
                ];
            })->values(),
            'permissions' => [
                'canManageFinance' => $canManageFinance,
            ],
            'scope' => $scope,
            'linkedBusinessUnits' => $linkedBusinessUnits,
        ]);
    }

    public function entries(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canAccess($user, $businessUnitId), 403);

        $year = (int) $request->integer('year', (int) now()->format('Y'));
        $selectedMonth = (int) max(1, min(12, $request->integer('month', (int) now()->format('n'))));
        $allowedBusinessUnitIds = $this->scopeService->allowedBusinessUnitIds($user, $businessUnitId);
        $departments = $this->scopeService->allowedDepartments($user, $businessUnitId);
        $departmentIds = $departments->pluck('id');
        $cycleIds = collect($allowedBusinessUnitIds)
            ->map(fn (int $allowedBusinessUnitId) => $this->findOrCreateCycle($allowedBusinessUnitId, $year, $user->id)->id);

        $lineItems = CashflowProjectionLineItem::query()
            ->with(['department.businessUnit', 'creator', 'updater'])
            ->whereIn('cycle_id', $cycleIds)
            ->whereIn('department_id', $departmentIds)
            ->whereMonth('transaction_date', $selectedMonth)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('CashflowProjection/Entries', [
            'year' => $year,
            'selectedMonth' => $selectedMonth,
            'departments' => $this->buildDepartmentOptions($departments)->values(),
            'lineItems' => $this->buildLineItemPayload($lineItems)->values(),
        ]);
    }

    public function settings(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->isFinanceUser($user, $businessUnitId), 403);

        $year = (int) $request->integer('year', (int) now()->format('Y'));
        $selectedMonth = (int) max(1, min(12, $request->integer('month', (int) now()->format('n'))));
        $cycle = $this->findOrCreateCycle($businessUnitId, $year, $user->id);

        $financeInputs = CashflowProjectionFinanceInput::query()
            ->where('cycle_id', $cycle->id)
            ->orderBy('month')
            ->get();

        $linkedUnits = CashflowProjectionLinkedUnit::query()
            ->with('linkedBusinessUnit')
            ->where('host_business_unit_id', $businessUnitId)
            ->get();

        $availableBusinessUnits = \App\Models\Core\BusinessUnit::query()
            ->where('is_active', true)
            ->where('id', '!=', $businessUnitId)
            ->whereNotIn('id', $linkedUnits->pluck('linked_business_unit_id'))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return Inertia::render('CashflowProjection/Settings', [
            'year' => $year,
            'selectedMonth' => $selectedMonth,
            'financeInputs' => $this->buildFinanceInputPayload(
                $financeInputs->load(['creator', 'updater', 'cycle'])
            )->values(),
            'linkedUnits' => $linkedUnits->map(fn (CashflowProjectionLinkedUnit $lu) => [
                'id' => $lu->id,
                'business_unit_id' => $lu->linked_business_unit_id,
                'code' => $lu->linkedBusinessUnit?->code,
                'name' => $lu->linkedBusinessUnit?->name,
            ])->values(),
            'availableBusinessUnits' => $availableBusinessUnits->map(fn ($bu) => [
                'id' => $bu->id,
                'code' => $bu->code,
                'name' => $bu->name,
            ])->values(),
        ]);
    }

    public function storeLineItem(StoreCashflowProjectionLineItemRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $isFinance = $this->accessService->isFinanceUser($user, $businessUnitId);
        $department = Department::query()->with('businessUnit')->findOrFail((int) $request->integer('department_id'));

        if ($isFinance && ! $this->scopeService->financeCanTargetDepartment($user, $businessUnitId, $department)) {
            return back()->withErrors([
                'department_id' => 'Departemen tidak berada dalam cakupan business unit aktif atau linked business unit.',
            ])->withInput();
        }

        if (! $isFinance && ! $this->scopeService->nonFinanceCanTargetDepartment($user, $businessUnitId, $department)) {
            abort(403);
        }

        $actionCode = (string) $request->string('action_code');

        if (! $this->templateService->isActionAllowedForDepartment($actionCode, $department)) {
            return back()->withErrors([
                'action_code' => 'Action tidak sesuai template departemen.',
            ])->withInput();
        }

        $actionMeta = $this->templateService->metaForActionCode($actionCode, $department);

        if (! $actionMeta) {
            return back()->withErrors([
                'action_code' => 'Action tidak valid.',
            ])->withInput();
        }

        $cycle = $this->findOrCreateCycle((int) $department->business_unit_id, (int) $request->integer('year'), $user->id);

        $lineItem = CashflowProjectionLineItem::query()->create([
            'cycle_id' => $cycle->id,
            'department_id' => $department->id,
            'flow_type' => $actionMeta['flow_type'],
            'action_code' => $actionCode,
            'transaction_date' => $request->date('transaction_date'),
            'due_date' => $request->date('due_date'),
            'is_estimated_date' => (bool) $request->boolean('is_estimated_date'),
            'amount' => $request->input('amount'),
            'description' => (string) $request->string('description'),
            'notes' => $request->filled('notes') ? (string) $request->string('notes') : null,
            'source_type' => 'manual',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $lineItem->load('department');
        $this->auditService->logLineItemAction(
            'created',
            $lineItem,
            $user,
            $this->scopeService->currentActorDepartment($user, $businessUnitId),
            null,
            $this->lineItemAuditValues($lineItem)
        );

        $transactionMonth = (int) ($request->date('transaction_date')?->format('n') ?? 0);
        $redirectParams = ['year' => $cycle->year];
        if ($transactionMonth >= 1 && $transactionMonth <= 12) {
            $redirectParams['month'] = $transactionMonth;
        }

        return redirect()
            ->route('cashflow-projection.entries', $redirectParams)
            ->with('success', 'Line item cashflow berhasil disimpan.');
    }

    public function updateLineItem(UpdateCashflowProjectionLineItemRequest $request, CashflowProjectionLineItem $lineItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $lineItem->load('department');
        $currentDepartment = $lineItem->department;
        abort_unless($currentDepartment instanceof Department, 404);

        $isFinance = $this->accessService->isFinanceUser($user, $businessUnitId);

        if ($isFinance && ! $this->scopeService->financeCanTargetDepartment($user, $businessUnitId, $currentDepartment)) {
            abort(403);
        }

        if (! $isFinance && ! $this->scopeService->nonFinanceCanTargetDepartment($user, $businessUnitId, $currentDepartment)) {
            abort(403);
        }

        $targetDepartment = Department::query()->with('businessUnit')->findOrFail((int) $request->integer('department_id'));

        if ($isFinance && ! $this->scopeService->financeCanTargetDepartment($user, $businessUnitId, $targetDepartment)) {
            return back()->withErrors([
                'department_id' => 'Departemen tidak berada dalam cakupan business unit aktif atau linked business unit.',
            ])->withInput();
        }

        if (! $isFinance && ! $this->scopeService->nonFinanceCanTargetDepartment($user, $businessUnitId, $targetDepartment)) {
            abort(403);
        }

        $actionCode = (string) $request->string('action_code');

        if (! $this->templateService->isActionAllowedForDepartment($actionCode, $targetDepartment)) {
            return back()->withErrors([
                'action_code' => 'Action tidak sesuai template departemen.',
            ])->withInput();
        }

        $actionMeta = $this->templateService->metaForActionCode($actionCode, $targetDepartment);

        if (! $actionMeta) {
            return back()->withErrors([
                'action_code' => 'Action tidak valid.',
            ])->withInput();
        }

        $oldValues = $this->lineItemAuditValues($lineItem);
        $targetCycle = $this->findOrCreateCycle((int) $targetDepartment->business_unit_id, (int) $request->integer('year'), $user->id);

        $lineItem->forceFill([
            'cycle_id' => $targetCycle->id,
            'department_id' => $targetDepartment->id,
            'flow_type' => $actionMeta['flow_type'],
            'action_code' => $actionCode,
            'transaction_date' => $request->date('transaction_date'),
            'due_date' => $request->date('due_date'),
            'is_estimated_date' => (bool) $request->boolean('is_estimated_date'),
            'amount' => $request->input('amount'),
            'description' => (string) $request->string('description'),
            'notes' => $request->filled('notes') ? (string) $request->string('notes') : null,
            'updated_by' => $user->id,
        ])->save();

        $lineItem->load('department');
        $this->auditService->logLineItemAction(
            'updated',
            $lineItem,
            $user,
            $this->scopeService->currentActorDepartment($user, $businessUnitId),
            $oldValues,
            $this->lineItemAuditValues($lineItem)
        );

        $transactionMonth = (int) ($request->date('transaction_date')?->format('n') ?? 0);
        $redirectParams = ['year' => $targetCycle->year];
        if ($transactionMonth >= 1 && $transactionMonth <= 12) {
            $redirectParams['month'] = $transactionMonth;
        }

        return redirect()
            ->route('cashflow-projection.entries', $redirectParams)
            ->with('success', 'Line item cashflow berhasil diperbarui.');
    }

    public function destroyLineItem(Request $request, CashflowProjectionLineItem $lineItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $lineItem->loadMissing('department', 'cycle');
        $department = $lineItem->department;
        $cycle = $lineItem->cycle;

        abort_unless($department instanceof Department, 404);
        abort_unless($cycle instanceof CashflowProjectionCycle, 404);

        $allowedBusinessUnitIds = $this->scopeService->allowedBusinessUnitIds($user, $businessUnitId);
        $allowedDepartments = $this->scopeService->allowedDepartments($user, $businessUnitId);

        abort_unless(in_array((int) $cycle->business_unit_id, $allowedBusinessUnitIds, true), 403);
        abort_unless($allowedDepartments->contains('id', $department->id), 403);

        $this->auditService->logDeletedLineItemAction(
            $lineItem,
            $user,
            $this->scopeService->currentActorDepartment($user, $businessUnitId),
            $this->lineItemAuditValues($lineItem)
        );

        $lineItem->delete();

        return redirect()
            ->route('cashflow-projection.entries', $this->resolveEntriesRedirectParams($request, $lineItem))
            ->with('success', 'Line item cashflow berhasil dihapus.');
    }

    public function upsertFinanceInput(UpsertCashflowProjectionFinanceInputRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->isFinanceUser($user, $businessUnitId), 403);

        $cycle = $this->findOrCreateCycle($businessUnitId, (int) $request->integer('year'), $user->id);

        $financeInput = CashflowProjectionFinanceInput::query()->firstOrNew([
            'cycle_id' => $cycle->id,
            'month' => (int) $request->integer('month'),
        ]);

        $wasRecentlyCreated = ! $financeInput->exists;
        $oldValues = $financeInput->exists ? $this->financeInputAuditValues($financeInput) : null;

        if (! $financeInput->exists) {
            $financeInput->created_by = $user->id;
        }

        $financeInput->cash_on_hand = $request->input('cash_on_hand');
        $financeInput->receivable_estimate = $request->input('receivable_estimate');
        $financeInput->upcoming_event_revenue_estimate = $request->input('upcoming_event_revenue_estimate');
        $financeInput->capital_injection_estimate = $request->input('capital_injection_estimate');
        $financeInput->other_income = $request->input('other_income');
        $financeInput->updated_by = $user->id;
        $financeInput->save();
        $financeInput->load('cycle');

        $this->auditService->logFinanceInputAction(
            $wasRecentlyCreated ? 'created' : 'updated',
            $financeInput,
            $user,
            $this->scopeService->currentActorDepartment($user, $businessUnitId),
            $oldValues,
            $this->financeInputAuditValues($financeInput)
        );

        $financeMonth = (int) $request->integer('month');
        $redirectParams = ['year' => $cycle->year];
        if ($financeMonth >= 1 && $financeMonth <= 12) {
            $redirectParams['month'] = $financeMonth;
        }

        return redirect()
            ->route('cashflow-projection.settings', $redirectParams)
            ->with('success', 'Input finance cashflow berhasil disimpan.');
    }

    public function storeLinkedUnit(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->isFinanceUser($user, $businessUnitId), 403);

        $request->validate([
            'linked_business_unit_id' => ['required', 'integer', 'exists:business_units,id'],
        ]);

        $linkedBuId = (int) $request->integer('linked_business_unit_id');

        if ($linkedBuId === $businessUnitId) {
            return back()->withErrors(['linked_business_unit_id' => 'Tidak bisa link ke business unit sendiri.']);
        }

        CashflowProjectionLinkedUnit::query()->firstOrCreate(
            [
                'host_business_unit_id' => $businessUnitId,
                'linked_business_unit_id' => $linkedBuId,
            ],
            [
                'created_by' => $user->id,
            ]
        );

        return back()->with('success', 'Linked business unit berhasil ditambahkan.');
    }

    public function destroyLinkedUnit(CashflowProjectionLinkedUnit $linkedUnit): RedirectResponse
    {
        /** @var \App\Models\Core\User $user */
        $user = request()->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->isFinanceUser($user, $businessUnitId), 403);
        abort_unless($linkedUnit->host_business_unit_id === $businessUnitId, 403);

        $linkedUnit->delete();

        return back()->with('success', 'Linked business unit berhasil dihapus.');
    }

    public function export(CashflowProjectionDashboardFilterRequest $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->isFinanceUser($user, $businessUnitId), 403);

        $dashboardFilters = $this->resolveDashboardFilters($request, $businessUnitId);
        $year = $dashboardFilters['year'];
        $cycle = $this->findOrCreateCycle($businessUnitId, $year, $user->id);

        $canManageFinance = $this->userHasFinanceAssignment($user, $businessUnitId);

        // Finance users see ALL departments in their BU; others see only their own
        if ($canManageFinance) {
            $departments = Department::query()
                ->with('businessUnit')
                ->where('business_unit_id', $businessUnitId)
                ->where('is_active', true)
                ->get();
        } else {
            $assignments = $user->activeBusinessUnits()
                ->with(['department.businessUnit', 'position'])
                ->where('business_unit_id', $businessUnitId)
                ->get();

            $departments = $assignments
                ->pluck('department')
                ->filter(fn ($department) => $department instanceof Department && $department->is_active)
                ->unique('id')
                ->values();
        }

        $linkedBuIds = $canManageFinance ? $this->getLinkedBusinessUnitIds($businessUnitId) : [];

        if ($canManageFinance && count($linkedBuIds) > 0) {
            $linkedDepartments = Department::query()
                ->with('businessUnit')
                ->whereIn('business_unit_id', $linkedBuIds)
                ->where('is_active', true)
                ->get();
            $departments = $departments->merge($linkedDepartments)->unique('id')->values();
        }

        $departmentIds = $departments->pluck('id');

        $lineItems = CashflowProjectionLineItem::query()
            ->with(['department.businessUnit', 'creator', 'updater'])
            ->where('cycle_id', $cycle->id)
            ->whereIn('department_id', $departmentIds)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $financeInputs = $canManageFinance
            ? CashflowProjectionFinanceInput::query()
                ->with(['creator', 'updater'])
                ->where('cycle_id', $cycle->id)
                ->orderBy('month')
                ->get()
            : collect();

        $scope = count($linkedBuIds) > 0
            ? (string) $request->string('scope', 'consolidated')
            : 'own';

        $allLineItems = $lineItems;

        if ($scope === 'consolidated' && count($linkedBuIds) > 0) {
            $linkedCycles = $this->getLinkedCycles($linkedBuIds, $year, $user->id);
            $linkedLineItems = CashflowProjectionLineItem::query()
                ->with(['department.businessUnit', 'creator', 'updater'])
                ->whereIn('cycle_id', $linkedCycles->pluck('id'))
                ->orderBy('transaction_date')
                ->orderBy('id')
                ->get();

            $allLineItems = $lineItems->merge($linkedLineItems)
                ->sortBy([
                    ['transaction_date', 'asc'],
                    ['id', 'asc'],
                ])
                ->values();

            $linkedFinanceInputs = CashflowProjectionFinanceInput::query()
                ->with(['creator', 'updater'])
                ->whereIn('cycle_id', $linkedCycles->pluck('id'))
                ->orderBy('month')
                ->get();

            foreach ($linkedFinanceInputs as $linkedInput) {
                $existing = $financeInputs->firstWhere('month', $linkedInput->month);
                if ($existing) {
                    $existing->cash_on_hand = (float) $existing->cash_on_hand + (float) $linkedInput->cash_on_hand;
                    $existing->receivable_estimate = (float) $existing->receivable_estimate + (float) $linkedInput->receivable_estimate;
                    $existing->upcoming_event_revenue_estimate = (float) $existing->upcoming_event_revenue_estimate + (float) $linkedInput->upcoming_event_revenue_estimate;
                    $existing->capital_injection_estimate = (float) $existing->capital_injection_estimate + (float) $linkedInput->capital_injection_estimate;
                    $existing->other_income = (float) $existing->other_income + (float) $linkedInput->other_income;
                } else {
                    $financeInputs->push($linkedInput);
                }
            }

            $financeInputs = $financeInputs->sortBy('month')->values();
        }

        $filteredLineItems = $this->filterLineItemsByPeriod(
            $allLineItems,
            $dashboardFilters['start'],
            $dashboardFilters['end']
        );

        $dailySummary = $this->buildPeriodDailySummary(
            $filteredLineItems,
            $dashboardFilters['start'],
            $dashboardFilters['end']
        );
        $monthlySummary = $this->buildMonthlySummary($allLineItems, $financeInputs);
        $summary = $this->buildDashboardSummary(
            $filteredLineItems,
            $financeInputs,
            $monthlySummary,
            $dashboardFilters['start'],
            $dashboardFilters['end']
        );

        $rawEntries = $this->buildLineItemPayload($allLineItems)->values();
        $financeInputRows = $this->buildFinanceInputPayload($financeInputs)->values();
        $periodLabel = $this->formatExportPeriodLabel($dashboardFilters);
        $scopeLabel = $scope === 'consolidated' ? 'Consolidated' : 'BU Only';
        $filename = $this->buildExportFilename($year, $dashboardFilters, $scope);

        $summaryRows = [
            [
                ['type' => 'String', 'value' => 'Cashflow Projection Export', 'style' => 'section'],
            ],
            [
                ['type' => 'String', 'value' => 'Selected Period', 'style' => 'header'],
                ['type' => 'String', 'value' => $periodLabel],
            ],
            [
                ['type' => 'String', 'value' => 'Scope', 'style' => 'header'],
                ['type' => 'String', 'value' => $scopeLabel],
            ],
            [
                ['type' => 'String', 'value' => 'Business Unit', 'style' => 'header'],
                ['type' => 'String', 'value' => (string) session('current_business_unit_name', '')],
            ],
            [
                ['type' => 'String', 'value' => 'Exported At', 'style' => 'header'],
                ['type' => 'String', 'value' => now()->format('Y-m-d H:i:s')],
            ],
            [['type' => 'String', 'value' => '', 'style' => 'text']],
            [
                ['type' => 'String', 'value' => 'Balance Snapshot', 'style' => 'header'],
                ['type' => 'Number', 'value' => $summary['total_balance'], 'style' => 'number'],
            ],
            [
                ['type' => 'String', 'value' => 'Period Inflow', 'style' => 'header'],
                ['type' => 'Number', 'value' => $summary['inflow'], 'style' => 'number'],
            ],
            [
                ['type' => 'String', 'value' => 'Period Outflow', 'style' => 'header'],
                ['type' => 'Number', 'value' => $summary['outflow'], 'style' => 'number'],
            ],
            [
                ['type' => 'String', 'value' => 'Finance Income', 'style' => 'header'],
                ['type' => 'Number', 'value' => $summary['finance_income'], 'style' => 'number'],
            ],
            [
                ['type' => 'String', 'value' => 'Net Cashflow', 'style' => 'header'],
                ['type' => 'Number', 'value' => $summary['net_cashflow'], 'style' => 'number'],
            ],
            [['type' => 'String', 'value' => '', 'style' => 'text']],
            [
                ['type' => 'String', 'value' => 'Month', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Inflow', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Outflow', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Finance Income', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Opening Balance', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Net', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Closing Balance', 'style' => 'header'],
                ['type' => 'String', 'value' => 'Warning', 'style' => 'header'],
            ],
        ];

        foreach ($monthlySummary as $row) {
            $summaryRows[] = [
                ['type' => 'String', 'value' => CarbonImmutable::create($year, (int) $row['month'], 1)->format('M Y'), 'style' => 'text'],
                ['type' => 'Number', 'value' => $row['plus'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['minus'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['finance_income'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['opening_balance'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['net'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['closing_balance'], 'style' => 'number'],
                ['type' => 'String', 'value' => $row['is_warning'] ? 'YES' : 'NO', 'style' => 'text'],
            ];
        }

        $dailyRows = [[
            ['type' => 'String', 'value' => 'Date', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Inflow', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Outflow', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Net', 'style' => 'header'],
        ]];

        foreach ($dailySummary as $row) {
            $dailyRows[] = [
                ['type' => 'String', 'value' => $row['date'], 'style' => 'date'],
                ['type' => 'Number', 'value' => $row['plus'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['minus'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['net'], 'style' => 'number'],
            ];
        }

        $rawEntryRows = [[
            ['type' => 'String', 'value' => 'Transaction Date', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Due Date', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Business Unit', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Department', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Category', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Flow Type', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Description', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Notes', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Amount', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Estimated Date', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Created By', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Created Department', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Last Edited By', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Last Edited Department', 'style' => 'header'],
        ]];

        foreach ($rawEntries as $row) {
            $rawEntryRows[] = [
                ['type' => 'String', 'value' => (string) $row['transaction_date'], 'style' => 'date'],
                ['type' => 'String', 'value' => (string) ($row['due_date'] ?? ''), 'style' => 'date'],
                ['type' => 'String', 'value' => (string) ($row['business_unit_code'] ?? ''), 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['department_name'] ?? ''), 'style' => 'text'],
                ['type' => 'String', 'value' => (string) $row['action_label'], 'style' => 'text'],
                ['type' => 'String', 'value' => $row['flow_type'] === 'in' ? 'Inflow' : 'Outflow', 'style' => 'text'],
                ['type' => 'String', 'value' => (string) $row['description'], 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['notes'] ?? ''), 'style' => 'text'],
                ['type' => 'Number', 'value' => $row['amount'], 'style' => 'number'],
                ['type' => 'String', 'value' => $row['is_estimated_date'] ? 'YES' : 'NO', 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['creator_name'] ?? ''), 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['creator_department_label'] ?? ''), 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['updater_name'] ?? ''), 'style' => 'text'],
                ['type' => 'String', 'value' => (string) ($row['updater_department_label'] ?? ''), 'style' => 'text'],
            ];
        }

        $financeRows = [[
            ['type' => 'String', 'value' => 'Month', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Cash on Hand', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Receivable Estimate', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Upcoming Revenue Estimate', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Capital Injection Estimate', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Other Income', 'style' => 'header'],
            ['type' => 'String', 'value' => 'Finance Income Total', 'style' => 'header'],
        ]];

        foreach ($financeInputRows as $row) {
            $financeRows[] = [
                ['type' => 'String', 'value' => CarbonImmutable::create($year, (int) $row['month'], 1)->format('M Y'), 'style' => 'text'],
                ['type' => 'Number', 'value' => $row['cash_on_hand'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['receivable_estimate'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['upcoming_event_revenue_estimate'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['capital_injection_estimate'], 'style' => 'number'],
                ['type' => 'Number', 'value' => $row['other_income'], 'style' => 'number'],
                ['type' => 'Number', 'value' => (float) $row['receivable_estimate'] + (float) $row['upcoming_event_revenue_estimate'] + (float) $row['capital_injection_estimate'] + (float) $row['other_income'], 'style' => 'number'],
            ];
        }

        // Export produces 4 sheets: Summary, Daily Movement, Raw Entries, and Finance Inputs.
        // Finance Inputs is an additional operational sheet for finance teams to review
        // their manual input entries separately from the consolidated raw data.
        $workbook = $this->buildExportWorkbookXml([
            'Summary' => $summaryRows,
            'Daily Movement' => $dailyRows,
            'Raw Entries' => $rawEntryRows,
            'Finance Inputs' => $financeRows,
        ]);

        return response()->streamDownload(function () use ($workbook) {
            echo $workbook;
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename='.$filename,
        ]);
    }

    private function formatExportPeriodLabel(array $dashboardFilters): string
    {
        if ($dashboardFilters['mode'] === 'year') {
            return 'FY '.$dashboardFilters['year'];
        }

        if ($dashboardFilters['mode'] === 'range') {
            return $dashboardFilters['start']->format('Y-m-d').' to '.$dashboardFilters['end']->format('Y-m-d');
        }

        return CarbonImmutable::create($dashboardFilters['year'], $dashboardFilters['month'], 1)->format('M Y');
    }

    private function buildExportFilename(int $year, array $dashboardFilters, string $scope): string
    {
        $scopeSegment = $scope === 'consolidated' ? 'consolidated' : 'bu-only';

        if ($dashboardFilters['mode'] === 'year') {
            return 'cashflow-projection-'.$scopeSegment.'-'.$year.'.xls';
        }

        if ($dashboardFilters['mode'] === 'range') {
            return 'cashflow-projection-'.$scopeSegment.'-'.$dashboardFilters['start']->format('Ymd').'-to-'.$dashboardFilters['end']->format('Ymd').'.xls';
        }

        return 'cashflow-projection-'.$scopeSegment.'-'.$year.'-'.str_pad((string) $dashboardFilters['month'], 2, '0', STR_PAD_LEFT).'.xls';
    }

    /**
     * @param  array<string, array<int, array<int, array{type: string, value: mixed, style: string}>>>  $worksheets
     */
    private function buildExportWorkbookXml(array $worksheets): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#E8EEF7" ss:Pattern="Solid"/></Style>';
        $xml .= '<Style ss:ID="section"><Font ss:Bold="1" ss:Size="14"/></Style>';
        $xml .= '<Style ss:ID="text"><Alignment ss:Vertical="Center"/></Style>';
        $xml .= '<Style ss:ID="date"><NumberFormat ss:Format="yyyy-mm-dd"/></Style>';
        $xml .= '<Style ss:ID="number"><NumberFormat ss:Format="#,##0"/></Style>';
        $xml .= '</Styles>';

        foreach ($worksheets as $name => $rows) {
            $xml .= '<Worksheet ss:Name="'.$this->escapeExportXml($name).'"><Table>';

            foreach ($rows as $row) {
                $xml .= '<Row>';

                foreach ($row as $cell) {
                    $styleId = (string) ($cell['style'] ?? 'text');
                    $style = $styleId !== '' ? ' ss:StyleID="'.$this->escapeExportXml($styleId).'"' : '';
                    $type = ($cell['type'] ?? 'String') === 'Number' ? 'Number' : 'String';
                    $value = $type === 'Number'
                        ? (string) ((float) ($cell['value'] ?? 0))
                        : $this->escapeExportXml((string) ($cell['value'] ?? ''));

                    $xml .= '<Cell'.$style.'><Data ss:Type="'.$type.'">'.$value.'</Data></Cell>';
                }

                $xml .= '</Row>';
            }

            $xml .= '</Table></Worksheet>';
        }

        $xml .= '</Workbook>';

        return $xml;
    }

    private function escapeExportXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1);
    }

    /**
     * @return array<int, array{date: string, plus: float, minus: float, net: float}>
     */
    private function buildDailySummary($lineItems, int $month): array
    {
        $daily = [];

        foreach ($lineItems as $item) {
            $itemMonth = (int) $item->transaction_date?->format('n');

            if ($itemMonth !== $month) {
                continue;
            }

            $dateKey = $item->transaction_date?->format('Y-m-d');

            if (! $dateKey) {
                continue;
            }

            if (! isset($daily[$dateKey])) {
                $daily[$dateKey] = [
                    'date' => $dateKey,
                    'plus' => 0.0,
                    'minus' => 0.0,
                    'net' => 0.0,
                ];
            }

            $amount = (float) $item->amount;

            if ($item->flow_type === 'in') {
                $daily[$dateKey]['plus'] += $amount;
            } else {
                $daily[$dateKey]['minus'] += $amount;
            }

            $daily[$dateKey]['net'] = $daily[$dateKey]['plus'] - $daily[$dateKey]['minus'];
        }

        ksort($daily);

        return array_values($daily);
    }

    /**
     * @return array<int, array{month: int, plus: float, minus: float, finance_income: float, opening_balance: float, net: float, closing_balance: float, is_warning: bool}>
     */
    private function buildMonthlySummary($lineItems, $financeInputs): array
    {
        $plusByMonth = array_fill(1, 12, 0.0);
        $minusByMonth = array_fill(1, 12, 0.0);

        foreach ($lineItems as $item) {
            $month = (int) $item->transaction_date?->format('n');
            if ($month < 1 || $month > 12) {
                continue;
            }

            $amount = (float) $item->amount;
            if ($item->flow_type === 'in') {
                $plusByMonth[$month] += $amount;
            } else {
                $minusByMonth[$month] += $amount;
            }
        }

        $financeByMonth = [];
        foreach ($financeInputs as $input) {
            $month = (int) $input->month;
            $financeByMonth[$month] = [
                'opening_balance' => (float) $input->cash_on_hand,
                'finance_income' => (float) $input->receivable_estimate +
                    (float) $input->upcoming_event_revenue_estimate +
                    (float) $input->capital_injection_estimate +
                    (float) $input->other_income,
            ];
        }

        $rows = [];
        for ($month = 1; $month <= 12; $month++) {
            $plus = $plusByMonth[$month];
            $minus = $minusByMonth[$month];
            $openingBalance = $financeByMonth[$month]['opening_balance'] ?? 0.0;
            $financeIncome = $financeByMonth[$month]['finance_income'] ?? 0.0;
            $net = $plus - $minus + $financeIncome;
            $closingBalance = $openingBalance + $net;

            $rows[] = [
                'month' => $month,
                'plus' => $plus,
                'minus' => $minus,
                'finance_income' => $financeIncome,
                'opening_balance' => $openingBalance,
                'net' => $net,
                'closing_balance' => $closingBalance,
                'is_warning' => $closingBalance < (int) config('features.cashflow.minimum_balance_global', 200000000),
            ];
        }

        return $rows;
    }

    /**
     * @return array{mode: string, year: int, month: int, start: CarbonImmutable, end: CarbonImmutable, available_years: array<int, int>}
     */
    private function resolveDashboardFilters(CashflowProjectionDashboardFilterRequest $request, int $businessUnitId): array
    {
        $mode = (string) $request->string('filter', 'month');
        $year = (int) $request->integer('year', (int) now()->format('Y'));
        $month = (int) max(1, min(12, $request->integer('month', (int) now()->format('n'))));

        if ($mode === 'year') {
            $startDate = CarbonImmutable::create($year, 1, 1)->startOfDay();
            $endDate = CarbonImmutable::create($year, 12, 31)->endOfDay();
        } elseif ($mode === 'range') {
            $startDate = CarbonImmutable::parse((string) $request->string('start_date'))->startOfDay();
            $endDate = CarbonImmutable::parse((string) $request->string('end_date'))->endOfDay();
        } else {
            $startDate = CarbonImmutable::create($year, $month, 1)->startOfDay();
            $endDate = $startDate->endOfMonth()->endOfDay();
        }

        return [
            'mode' => $mode,
            'year' => $year,
            'month' => $month,
            'start' => $startDate,
            'end' => $endDate,
            'available_years' => $this->resolveAvailableYears($businessUnitId, $year),
        ];
    }

    /**
     * @param  Collection<int, CashflowProjectionLineItem>  $lineItems
     * @return Collection<int, CashflowProjectionLineItem>
     */
    private function filterLineItemsByPeriod(Collection $lineItems, CarbonImmutable $startDate, CarbonImmutable $endDate): Collection
    {
        return $lineItems
            ->filter(function (CashflowProjectionLineItem $item) use ($startDate, $endDate) {
                if (! $item->transaction_date) {
                    return false;
                }

                $transactionDate = CarbonImmutable::instance($item->transaction_date);

                return $transactionDate->betweenIncluded($startDate, $endDate);
            })
            ->values();
    }

    /**
     * @param  Collection<int, CashflowProjectionLineItem>  $filteredLineItems
     * @return array<int, array{date: string, plus: float, minus: float, net: float}>
     */
    private function buildPeriodDailySummary(Collection $filteredLineItems, CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $daily = [];
        $cursor = $startDate->startOfDay();
        $lastDate = $endDate->startOfDay();

        while ($cursor->lessThanOrEqualTo($lastDate)) {
            $dateKey = $cursor->format('Y-m-d');
            $daily[$dateKey] = [
                'date' => $dateKey,
                'plus' => 0.0,
                'minus' => 0.0,
                'net' => 0.0,
            ];
            $cursor = $cursor->addDay();
        }

        foreach ($filteredLineItems as $item) {
            $dateKey = $item->transaction_date?->format('Y-m-d');

            if (! $dateKey || ! isset($daily[$dateKey])) {
                continue;
            }

            $amount = (float) $item->amount;

            if ($item->flow_type === 'in') {
                $daily[$dateKey]['plus'] += $amount;
            } else {
                $daily[$dateKey]['minus'] += $amount;
            }

            $daily[$dateKey]['net'] = $daily[$dateKey]['plus'] - $daily[$dateKey]['minus'];
        }

        return array_values($daily);
    }

    /**
     * @param  Collection<int, CashflowProjectionLineItem>  $filteredLineItems
     * @param  Collection<int, CashflowProjectionFinanceInput>  $financeInputs
     * @param  array<int, array{month: int, plus: float, minus: float, finance_income: float, opening_balance: float, net: float, closing_balance: float, is_warning: bool}>  $monthlySummary
     * @return array{total_balance: float, inflow: float, outflow: float, finance_income: float, net_cashflow: float}
     */
    private function buildDashboardSummary(
        Collection $filteredLineItems,
        Collection $financeInputs,
        array $monthlySummary,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate
    ): array {
        $monthsInScope = $this->monthsInPeriod($startDate, $endDate);

        $inflow = (float) $filteredLineItems
            ->where('flow_type', 'in')
            ->sum(fn (CashflowProjectionLineItem $item) => (float) $item->amount);

        $outflow = (float) $filteredLineItems
            ->where('flow_type', 'out')
            ->sum(fn (CashflowProjectionLineItem $item) => (float) $item->amount);

        $financeIncome = (float) $financeInputs
            ->filter(fn (CashflowProjectionFinanceInput $input) => in_array((int) $input->month, $monthsInScope, true))
            ->sum(function (CashflowProjectionFinanceInput $input) {
                return (float) $input->receivable_estimate
                    + (float) $input->upcoming_event_revenue_estimate
                    + (float) $input->capital_injection_estimate
                    + (float) $input->other_income;
            });

        $snapshot = collect($monthlySummary)
            ->filter(fn (array $row) => in_array((int) $row['month'], $monthsInScope, true))
            ->reverse()
            ->first(function (array $row) {
                return $row['plus'] > 0
                    || $row['minus'] > 0
                    || $row['finance_income'] > 0
                    || $row['opening_balance'] > 0;
            });

        if (! $snapshot) {
            $snapshot = collect($monthlySummary)
                ->filter(fn (array $row) => in_array((int) $row['month'], $monthsInScope, true))
                ->last();
        }

        return [
            'total_balance' => (float) ($snapshot['closing_balance'] ?? 0.0),
            'inflow' => $inflow,
            'outflow' => $outflow,
            'finance_income' => $financeIncome,
            'net_cashflow' => $inflow - $outflow + $financeIncome,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function resolveAvailableYears(int $businessUnitId, int $selectedYear): array
    {
        return CashflowProjectionCycle::query()
            ->where('business_unit_id', $businessUnitId)
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->push($selectedYear)
            ->push((int) now()->format('Y'))
            ->push((int) now()->subYear()->format('Y'))
            ->push((int) now()->addYear()->format('Y'))
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function monthsInPeriod(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $months = [];
        $cursor = $startDate->startOfMonth();
        $lastMonth = $endDate->startOfMonth();

        while ($cursor->lessThanOrEqualTo($lastMonth)) {
            $months[] = (int) $cursor->format('n');
            $cursor = $cursor->addMonth();
        }

        return array_values(array_unique($months));
    }

    /**
     * @return array{year: int, month?: int}
     */
    private function resolveEntriesRedirectParams(Request $request, ?CashflowProjectionLineItem $lineItem = null): array
    {
        $year = (int) $request->integer('year', $lineItem?->cycle?->year ?? (int) now()->format('Y'));
        $month = (int) $request->integer('month', $lineItem?->transaction_date?->format('n') ?? (int) now()->format('n'));

        $redirectParams = [
            'year' => $year,
        ];

        if ($month >= 1 && $month <= 12) {
            $redirectParams['month'] = $month;
        }

        return $redirectParams;
    }

    private function findOrCreateCycle(int $businessUnitId, int $year, int $userId): CashflowProjectionCycle
    {
        $cycle = CashflowProjectionCycle::query()->firstOrCreate(
            [
                'business_unit_id' => $businessUnitId,
                'year' => $year,
            ],
            [
                'status' => 'draft',
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        if ($cycle->updated_by === null) {
            $cycle->forceFill(['updated_by' => $userId])->save();
        }

        return $cycle;
    }

    private function userAssignedToDepartment(User $user, int $businessUnitId, int $departmentId): bool
    {
        return $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->where('department_id', $departmentId)
            ->exists();
    }

    private function userHasFinanceAssignment(User $user, int $businessUnitId): bool
    {
        return $user->activeBusinessUnits()
            ->where('business_unit_id', $businessUnitId)
            ->whereHas('department', function ($query) {
                $query->whereIn('code', ['CFC', 'FIN'])
                    ->orWhere('name', 'like', '%Finance%');
            })
            ->exists();
    }

    /**
     * Get linked business unit IDs for cashflow projection.
     *
     * @return array<int, int>
     */
    private function getLinkedBusinessUnitIds(int $hostBusinessUnitId): array
    {
        return CashflowProjectionLinkedUnit::query()
            ->where('host_business_unit_id', $hostBusinessUnitId)
            ->pluck('linked_business_unit_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Get cycles for linked business units.
     *
     * @param  array<int, int>  $linkedBuIds
     * @return Collection<int, CashflowProjectionCycle>
     */
    private function getLinkedCycles(array $linkedBuIds, int $year, int $userId): Collection
    {
        $cycles = collect();

        foreach ($linkedBuIds as $buId) {
            $cycles->push($this->findOrCreateCycle($buId, $year, $userId));
        }

        return $cycles;
    }

    /**
     * Get line items from linked BU cycles.
     *
     * @param  Collection<int, CashflowProjectionCycle>  $linkedCycles
     * @return Collection<int, CashflowProjectionLineItem>
     */
    private function getLinkedLineItems(Collection $linkedCycles): Collection
    {
        if ($linkedCycles->isEmpty()) {
            return collect();
        }

        $cycleIds = $linkedCycles->pluck('id');

        return CashflowProjectionLineItem::query()
            ->with('department')
            ->whereIn('cycle_id', $cycleIds)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @return Collection<int, array<string, mixed>>
     */
    private function buildDepartmentOptions(Collection $departments): Collection
    {
        return $departments->map(function (Department $department) {
            return [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'business_unit_id' => $department->business_unit_id,
                'business_unit_code' => $department->businessUnit?->code,
                'business_unit_name' => $department->businessUnit?->name,
                'template_type' => $this->templateService->templateTypeForDepartment($department),
                'actions' => $this->templateService->actionOptionsForDepartment($department),
            ];
        });
    }

    /**
     * @param  Collection<int, CashflowProjectionLineItem>  $lineItems
     * @return Collection<int, array<string, mixed>>
     */
    private function buildLineItemPayload(Collection $lineItems): Collection
    {
        $auditMetaById = $this->resolveAuditMetadata('line_item', $lineItems->pluck('id')->all());

        return $lineItems->map(function (CashflowProjectionLineItem $item) use ($auditMetaById) {
            $meta = $this->templateService->metaForActionCode($item->action_code, $item->department);
            $auditMeta = $auditMetaById[$item->id] ?? [];

            return [
                'id' => $item->id,
                'department_id' => $item->department_id,
                'department_code' => $item->department?->code,
                'department_name' => $item->department?->name,
                'business_unit_id' => $item->department?->business_unit_id,
                'business_unit_code' => $item->department?->businessUnit?->code,
                'business_unit_name' => $item->department?->businessUnit?->name,
                'flow_type' => $item->flow_type,
                'action_code' => $item->action_code,
                'action_label' => $this->templateService->displayLabelForAction($item->action_code, $item->department) ?? $meta['label'] ?? $item->action_code,
                'transaction_date' => optional($item->transaction_date)->format('Y-m-d'),
                'due_date' => optional($item->due_date)->format('Y-m-d'),
                'amount' => (float) $item->amount,
                'description' => $item->description,
                'notes' => $item->notes,
                'is_estimated_date' => (bool) $item->is_estimated_date,
                'creator_name' => $auditMeta['creator_name'] ?? $item->creator?->name,
                'creator_department_label' => $auditMeta['creator_department_label'] ?? $item->creator?->primaryDepartment?->name,
                'has_edit_history' => (bool) ($auditMeta['has_edit_history'] ?? false),
                'updater_name' => $auditMeta['updater_name'] ?? $item->updater?->name,
                'updater_department_label' => $auditMeta['updater_department_label'] ?? $item->updater?->primaryDepartment?->name,
            ];
        });
    }

    /**
     * @param  Collection<int, CashflowProjectionFinanceInput>  $financeInputs
     * @return Collection<int, array<string, mixed>>
     */
    private function buildFinanceInputPayload(Collection $financeInputs): Collection
    {
        $auditMetaById = $this->resolveAuditMetadata('finance_input', $financeInputs->pluck('id')->all());

        return $financeInputs->map(function (CashflowProjectionFinanceInput $input) use ($auditMetaById) {
            $auditMeta = $auditMetaById[$input->id] ?? [];

            return [
                'id' => $input->id,
                'month' => $input->month,
                'cash_on_hand' => (float) $input->cash_on_hand,
                'receivable_estimate' => (float) $input->receivable_estimate,
                'upcoming_event_revenue_estimate' => (float) $input->upcoming_event_revenue_estimate,
                'capital_injection_estimate' => (float) $input->capital_injection_estimate,
                'other_income' => (float) $input->other_income,
                'creator_name' => $auditMeta['creator_name'] ?? $input->creator?->name,
                'creator_department_label' => $auditMeta['creator_department_label'] ?? $input->creator?->primaryDepartment?->name,
                'updater_name' => $auditMeta['updater_name'] ?? $input->updater?->name,
                'updater_department_label' => $auditMeta['updater_department_label'] ?? $input->updater?->primaryDepartment?->name,
            ];
        });
    }

    /**
     * @param  array<int, int>  $auditableIds
     * @return array<int, array<string, string|null>>
     */
    private function resolveAuditMetadata(string $auditableType, array $auditableIds): array
    {
        if ($auditableIds === []) {
            return [];
        }

        $auditLogs = CashflowProjectionAuditLog::query()
            ->where('auditable_type', $auditableType)
            ->whereIn('auditable_id', $auditableIds)
            ->orderBy('created_at')
            ->get()
            ->groupBy('auditable_id');

        $metadata = [];

        foreach ($auditLogs as $auditableId => $logs) {
            $createdLog = $logs->firstWhere('action', 'created');
            $latestLog = $logs->last();

            $metadata[(int) $auditableId] = [
                'creator_name' => $createdLog?->actor_user_name,
                'creator_department_label' => $createdLog?->actor_department_label,
                'has_edit_history' => $logs->contains(
                    fn (CashflowProjectionAuditLog $log) => $log->action === 'updated'
                ),
                'updater_name' => $latestLog?->actor_user_name,
                'updater_department_label' => $latestLog?->actor_department_label,
            ];
        }

        return $metadata;
    }

    /**
     * @return array<string, mixed>
     */
    private function lineItemAuditValues(CashflowProjectionLineItem $lineItem): array
    {
        return [
            'cycle_id' => $lineItem->cycle_id,
            'department_id' => $lineItem->department_id,
            'action_code' => $lineItem->action_code,
            'flow_type' => $lineItem->flow_type,
            'transaction_date' => optional($lineItem->transaction_date)->format('Y-m-d'),
            'due_date' => optional($lineItem->due_date)->format('Y-m-d'),
            'is_estimated_date' => (bool) $lineItem->is_estimated_date,
            'amount' => (float) $lineItem->amount,
            'description' => $lineItem->description,
            'notes' => $lineItem->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function financeInputAuditValues(CashflowProjectionFinanceInput $financeInput): array
    {
        return [
            'cycle_id' => $financeInput->cycle_id,
            'month' => $financeInput->month,
            'cash_on_hand' => (float) $financeInput->cash_on_hand,
            'receivable_estimate' => (float) $financeInput->receivable_estimate,
            'upcoming_event_revenue_estimate' => (float) $financeInput->upcoming_event_revenue_estimate,
            'capital_injection_estimate' => (float) $financeInput->capital_injection_estimate,
            'other_income' => (float) $financeInput->other_income,
        ];
    }

    public function downloadImportTemplate(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $year = (int) $request->integer('year', (int) now()->format('Y'));
        $month = (int) max(1, min(12, $request->integer('month', (int) now()->format('n'))));

        return $this->entryImportTemplateService->generateTemplate($businessUnitId, $user, $year, $month);
    }

    public function importEntries(ImportCashflowProjectionEntriesRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $file = $request->file('file');

        $result = $this->entryImportService->import(
            $file->getRealPath() ?: $file->path(),
            $file->getClientOriginalName(),
            $user,
            $businessUnitId
        );

        return redirect()
            ->route('cashflow-projection.entries', [
                'year' => (int) $request->integer('context_year', (int) now()->format('Y')),
                'month' => (int) $request->integer('context_month', (int) now()->format('n')),
            ])
            ->with('cashflow_import', $result);
    }
}
