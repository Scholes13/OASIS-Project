<?php

namespace App\Http\Controllers\Modules\CashflowProjection;

use App\Actions\Modules\CashflowProjection\DestroyCashflowLineItemAction;
use App\Actions\Modules\CashflowProjection\StoreCashflowLineItemAction;
use App\Actions\Modules\CashflowProjection\UpdateCashflowLineItemAction;
use App\Actions\Modules\CashflowProjection\UpsertFinanceInputAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CashflowProjection\BulkDestroyCashflowProjectionLineItemsRequest;
use App\Http\Requests\CashflowProjection\CashflowProjectionDashboardFilterRequest;
use App\Http\Requests\CashflowProjection\ConfirmCashflowProjectionImportRequest;
use App\Http\Requests\CashflowProjection\ImportCashflowProjectionEntriesRequest;
use App\Http\Requests\CashflowProjection\PreviewCashflowProjectionImportRequest;
use App\Http\Requests\CashflowProjection\StoreCashflowProjectionLineItemRequest;
use App\Http\Requests\CashflowProjection\UpdateCashflowProjectionLineItemRequest;
use App\Http\Requests\CashflowProjection\UpsertCashflowProjectionFinanceInputRequest;
use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Models\Modules\CashflowProjection\CashflowProjectionLinkedUnit;
use App\Services\Modules\CashflowProjection\CashflowDashboardComposer;
use App\Services\Modules\CashflowProjection\CashflowExcelBuilder;
use App\Services\Modules\CashflowProjection\CashflowProjectionAccessService;
use App\Services\Modules\CashflowProjection\CashflowProjectionEntryImportService;
use App\Services\Modules\CashflowProjection\CashflowProjectionEntryImportTemplateService;
use App\Services\Modules\CashflowProjection\CashflowProjectionPayloadFormatter;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopePolicy;
use App\Services\Modules\CashflowProjection\CashflowProjectionScopeService;
use App\Services\Modules\CashflowProjection\CashflowProjectionTemplateService;
use App\Services\Modules\CashflowProjection\CashflowSummaryCalculator;
use App\Services\Modules\CashflowProjection\Import\CashflowImportConfirmService;
use App\Services\Modules\CashflowProjection\Import\CashflowImportPreviewService;
use App\Services\Modules\CashflowProjection\LinkedCycleMerger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashflowProjectionController extends Controller
{
    public function __construct(
        protected CashflowProjectionAccessService $accessService,
        protected CashflowProjectionTemplateService $templateService,
        protected CashflowProjectionScopeService $scopeService,
        protected CashflowProjectionScopePolicy $scopePolicy,
        protected CashflowProjectionEntryImportTemplateService $entryImportTemplateService,
        protected CashflowImportPreviewService $importPreviewService,
        protected CashflowImportConfirmService $importConfirmService,
        protected CashflowProjectionEntryImportService $entryImportService,
        protected CashflowSummaryCalculator $summaryCalculator,
        protected CashflowDashboardComposer $dashboardComposer,
        protected LinkedCycleMerger $linkedCycleMerger,
        protected CashflowExcelBuilder $excelBuilder,
        protected CashflowProjectionPayloadFormatter $payloadFormatter,
        protected StoreCashflowLineItemAction $storeLineItemAction,
        protected UpdateCashflowLineItemAction $updateLineItemAction,
        protected DestroyCashflowLineItemAction $destroyLineItemAction,
        protected UpsertFinanceInputAction $upsertFinanceInputAction
    ) {}

    public function index(CashflowProjectionDashboardFilterRequest $request): Response|\Symfony\Component\HttpFoundation\Response
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canAccess($user, $businessUnitId), 403);

        if (! $this->accessService->isFinanceUser($user, $businessUnitId)) {
            return Inertia::location(route('cashflow-projection.entries'));
        }

        $data = $this->dashboardComposer->compose(
            $request,
            $user,
            $businessUnitId,
            sortDirection: 'desc',
            eager: ['department'],
        );

        $dashboardFilters = $data['dashboardFilters'];
        $cycle = $data['cycle'];

        return Inertia::render('CashflowProjection/Index', [
            'year' => $data['year'],
            'selectedMonth' => $dashboardFilters['month'],
            'cycle' => [
                'id' => $cycle->id,
                'status' => $cycle->status,
                'year' => $cycle->year,
            ],
            'minimumBalanceGlobal' => (int) config('features.cashflow.minimum_balance_global', 200000000),
            'filters' => [
                'mode' => $dashboardFilters['mode'],
                'year' => $data['year'],
                'month' => $dashboardFilters['month'],
                'start_date' => $dashboardFilters['start']->format('Y-m-d'),
                'end_date' => $dashboardFilters['end']->format('Y-m-d'),
                'available_years' => $dashboardFilters['available_years'],
            ],
            'summary' => $data['summary'],
            'dailySummary' => $data['dailySummary'],
            'monthlySummary' => $data['monthlySummary'],
            'departments' => $data['departments']->map(function (Department $department) {
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
            'lineItems' => $this->payloadFormatter->buildDashboardLineItems($data['filteredLineItems']),
            'financeInputs' => $this->payloadFormatter->buildDashboardFinanceInputs($data['financeInputs']),
            'permissions' => [
                'canManageFinance' => $data['canManageFinance'],
            ],
            'scope' => $data['scope'],
            'linkedBusinessUnits' => $data['linkedBusinessUnits'],
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
        $search = trim((string) $request->query('search', ''));
        $allowedBusinessUnitIds = $this->scopeService->allowedBusinessUnitIds($user, $businessUnitId);
        $departments = $this->scopeService->allowedDepartments($user, $businessUnitId);
        $departmentIds = $departments->pluck('id');
        $cycleIds = collect($allowedBusinessUnitIds)
            ->map(fn (int $allowedBusinessUnitId) => $this->linkedCycleMerger->findOrCreateCycle($allowedBusinessUnitId, $year, $user->id)->id);

        $lineItems = CashflowProjectionLineItem::query()
            ->with(['department.businessUnit', 'creator', 'updater'])
            ->whereIn('cycle_id', $cycleIds)
            ->whereIn('department_id', $departmentIds)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $likeSearch = "%{$search}%";
                    $searchQuery->where('no_dokumen', 'like', $likeSearch)
                        ->orWhere('nama_vendor', 'like', $likeSearch)
                        ->orWhere('description', 'like', $likeSearch)
                        ->orWhere('keterangan', 'like', $likeSearch);
                });
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $lineItemPayload = $this->payloadFormatter->buildLineItemPayload($lineItems->getCollection());

        return Inertia::render('CashflowProjection/Entries', [
            'year' => $year,
            'selectedMonth' => $selectedMonth,
            'departments' => $this->payloadFormatter->buildDepartmentOptions($departments)->values(),
            'lineItems' => [
                'data' => $lineItemPayload->values(),
                'meta' => [
                    'current_page' => $lineItems->currentPage(),
                    'last_page' => $lineItems->lastPage(),
                    'per_page' => $lineItems->perPage(),
                    'total' => $lineItems->total(),
                ],
                'links' => [
                    'first' => $lineItems->url(1),
                    'last' => $lineItems->url($lineItems->lastPage()),
                    'prev' => $lineItems->previousPageUrl(),
                    'next' => $lineItems->nextPageUrl(),
                ],
            ],
            'filters' => [
                'search' => $search,
            ],
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
        $cycle = $this->linkedCycleMerger->findOrCreateCycle($businessUnitId, $year, $user->id);

        $financeInputs = CashflowProjectionFinanceInput::query()
            ->where('cycle_id', $cycle->id)
            ->orderBy('month')
            ->get();

        $linkedUnits = CashflowProjectionLinkedUnit::query()
            ->with('linkedBusinessUnit')
            ->where('host_business_unit_id', $businessUnitId)
            ->get();

        $availableBusinessUnits = BusinessUnit::query()
            ->where('is_active', true)
            ->where('id', '!=', $businessUnitId)
            ->whereNotIn('id', $linkedUnits->pluck('linked_business_unit_id'))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        return Inertia::render('CashflowProjection/Settings', [
            'year' => $year,
            'selectedMonth' => $selectedMonth,
            'financeInputs' => $this->payloadFormatter->buildFinanceInputPayload(
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

        $result = $this->storeLineItemAction->execute($request, $user, $businessUnitId);

        if (! $result['ok']) {
            return back()->withErrors($result['errors'])->withInput();
        }

        return redirect()
            ->route('cashflow-projection.entries', $result['redirect_params'])
            ->with('success', 'Line item cashflow berhasil disimpan.');
    }

    public function updateLineItem(UpdateCashflowProjectionLineItemRequest $request, CashflowProjectionLineItem $lineItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $result = $this->updateLineItemAction->execute($request, $lineItem, $user, $businessUnitId);

        if (! $result['ok']) {
            return back()->withErrors($result['errors'])->withInput();
        }

        return redirect()
            ->route('cashflow-projection.entries', $result['redirect_params'])
            ->with('success', 'Line item cashflow berhasil diperbarui.');
    }

    public function destroyLineItem(Request $request, CashflowProjectionLineItem $lineItem): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $year = $request->filled('year') ? (int) $request->integer('year') : null;
        $month = $request->filled('month') ? (int) $request->integer('month') : null;

        $result = $this->destroyLineItemAction->execute($lineItem, $user, $businessUnitId, $year, $month);

        return redirect()
            ->route('cashflow-projection.entries', $result['redirect_params'])
            ->with('success', 'Line item cashflow berhasil dihapus.');
    }

    public function bulkDestroyLineItems(BulkDestroyCashflowProjectionLineItemsRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $validated = $request->validated();

        $year = isset($validated['year']) ? (int) $validated['year'] : null;
        $month = isset($validated['month']) ? (int) $validated['month'] : null;
        $deletedCount = 0;

        DB::transaction(function () use ($validated, $user, $businessUnitId, $year, $month, &$deletedCount): void {
            CashflowProjectionLineItem::query()
                ->whereIn('id', $validated['line_item_ids'])
                ->orderBy('id')
                ->get()
                ->each(function (CashflowProjectionLineItem $lineItem) use ($user, $businessUnitId, $year, $month, &$deletedCount): void {
                    $this->destroyLineItemAction->execute($lineItem, $user, $businessUnitId, $year, $month);
                    $deletedCount++;
                });
        });

        $redirectParams = [];
        if ($year !== null) {
            $redirectParams['year'] = $year;
        }
        if ($month !== null) {
            $redirectParams['month'] = $month;
        }

        return redirect()
            ->route('cashflow-projection.entries', $redirectParams)
            ->with('success', $deletedCount.' line item cashflow berhasil dihapus.');
    }

    public function upsertFinanceInput(UpsertCashflowProjectionFinanceInputRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->isFinanceUser($user, $businessUnitId), 403);

        $result = $this->upsertFinanceInputAction->execute($request, $user, $businessUnitId);

        return redirect()
            ->route('cashflow-projection.settings', $result['redirect_params'])
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
        /** @var User $user */
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

        $data = $this->dashboardComposer->compose(
            $request,
            $user,
            $businessUnitId,
            sortDirection: 'asc',
            eager: ['department.businessUnit', 'creator', 'updater'],
            financeEagerAudit: true,
        );

        $rawEntries = $this->payloadFormatter->buildLineItemPayload($data['allLineItems'])->values();
        $financeInputRows = $this->payloadFormatter->buildFinanceInputPayload($data['financeInputs'])->values();

        return $this->excelBuilder->streamWorkbook(
            $data['year'],
            $data['dashboardFilters'],
            $data['scope'],
            $data['summary'],
            $data['monthlySummary'],
            $data['dailySummary'],
            $rawEntries,
            $financeInputRows
        );
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

    public function previewImport(PreviewCashflowProjectionImportRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $file = $request->file('file');

        return response()->json($this->importPreviewService->preview($file, $user, $businessUnitId));
    }

    public function confirmImport(ConfirmCashflowProjectionImportRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $validated = $request->validated();

        return response()->json($this->importConfirmService->confirm(
            $validated['rows'],
            $user,
            $businessUnitId,
            (int) $validated['context_year'],
            (int) $validated['context_month']
        ));
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
