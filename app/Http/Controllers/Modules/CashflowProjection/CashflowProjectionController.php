<?php

namespace App\Http\Controllers\Modules\CashflowProjection;

use App\Http\Controllers\Controller;
use App\Http\Requests\CashflowProjection\StoreCashflowProjectionLineItemRequest;
use App\Http\Requests\CashflowProjection\UpsertCashflowProjectionFinanceInputRequest;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Modules\CashflowProjection\CashflowProjectionCycle;
use App\Models\Modules\CashflowProjection\CashflowProjectionFinanceInput;
use App\Models\Modules\CashflowProjection\CashflowProjectionLineItem;
use App\Services\Modules\CashflowProjection\CashflowProjectionAccessService;
use App\Services\Modules\CashflowProjection\CashflowProjectionTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashflowProjectionController extends Controller
{
    private const MINIMUM_BALANCE_GLOBAL = 200000000;

    public function __construct(
        protected CashflowProjectionAccessService $accessService,
        protected CashflowProjectionTemplateService $templateService
    ) {}

    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canAccess($user, $businessUnitId), 403);

        // Dashboard is finance-only; non-finance users should use Entries page
        if (! $this->accessService->isFinanceUser($user, $businessUnitId)) {
            return Inertia::location(route('cashflow-projection.entries'));
        }

        $year = (int) $request->integer('year', (int) now()->format('Y'));
        $selectedMonth = (int) max(1, min(12, $request->integer('month', (int) now()->format('n'))));
        $cycle = $this->findOrCreateCycle($businessUnitId, $year, $user->id);

        $assignments = $user->activeBusinessUnits()
            ->with(['department', 'position'])
            ->where('business_unit_id', $businessUnitId)
            ->get();

        $departments = $assignments
            ->pluck('department')
            ->filter(fn ($department) => $department instanceof Department && $department->is_active)
            ->unique('id')
            ->values();

        $departmentIds = $departments->pluck('id');

        $lineItems = CashflowProjectionLineItem::query()
            ->with('department')
            ->where('cycle_id', $cycle->id)
            ->whereIn('department_id', $departmentIds)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $canManageFinance = $this->userHasFinanceAssignment($user, $businessUnitId);

        $financeInputs = $canManageFinance
            ? CashflowProjectionFinanceInput::query()
                ->where('cycle_id', $cycle->id)
                ->orderBy('month')
                ->get()
            : collect();

        $dailySummary = $this->buildDailySummary($lineItems, $selectedMonth);
        $monthlySummary = $this->buildMonthlySummary($lineItems, $financeInputs);

        return Inertia::render('CashflowProjection/Index', [
            'year' => $year,
            'selectedMonth' => $selectedMonth,
            'cycle' => [
                'id' => $cycle->id,
                'status' => $cycle->status,
                'year' => $cycle->year,
            ],
            'minimumBalanceGlobal' => self::MINIMUM_BALANCE_GLOBAL,
            'dailySummary' => $dailySummary,
            'monthlySummary' => $monthlySummary,
            'departments' => $departments->map(function (Department $department) {
                return [
                    'id' => $department->id,
                    'code' => $department->code,
                    'name' => $department->name,
                    'template_type' => $this->templateService->templateTypeForDepartment($department),
                    'actions' => $this->templateService->actionOptionsForDepartment($department),
                ];
            })->values(),
            'lineItems' => $lineItems->map(function (CashflowProjectionLineItem $item) {
                $meta = $this->templateService->metaForActionCode($item->action_code, $item->department);

                return [
                    'id' => $item->id,
                    'department_id' => $item->department_id,
                    'department_code' => $item->department?->code,
                    'department_name' => $item->department?->name,
                    'flow_type' => $item->flow_type,
                    'action_code' => $item->action_code,
                    'action_label' => $meta['label'] ?? $item->action_code,
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
        $cycle = $this->findOrCreateCycle($businessUnitId, $year, $user->id);

        $assignments = $user->activeBusinessUnits()
            ->with(['department', 'position'])
            ->where('business_unit_id', $businessUnitId)
            ->get();

        $departments = $assignments
            ->pluck('department')
            ->filter(fn ($department) => $department instanceof Department && $department->is_active)
            ->unique('id')
            ->values();

        $departmentIds = $departments->pluck('id');

        $lineItems = CashflowProjectionLineItem::query()
            ->with('department')
            ->where('cycle_id', $cycle->id)
            ->whereIn('department_id', $departmentIds)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('CashflowProjection/Entries', [
            'year' => $year,
            'selectedMonth' => $selectedMonth,
            'departments' => $departments->map(function (Department $department) {
                return [
                    'id' => $department->id,
                    'code' => $department->code,
                    'name' => $department->name,
                    'template_type' => $this->templateService->templateTypeForDepartment($department),
                    'actions' => $this->templateService->actionOptionsForDepartment($department),
                ];
            })->values(),
            'lineItems' => $lineItems->map(function (CashflowProjectionLineItem $item) {
                $meta = $this->templateService->metaForActionCode($item->action_code, $item->department);

                return [
                    'id' => $item->id,
                    'department_id' => $item->department_id,
                    'department_code' => $item->department?->code,
                    'department_name' => $item->department?->name,
                    'flow_type' => $item->flow_type,
                    'action_code' => $item->action_code,
                    'action_label' => $meta['label'] ?? $item->action_code,
                    'transaction_date' => optional($item->transaction_date)->format('Y-m-d'),
                    'due_date' => optional($item->due_date)->format('Y-m-d'),
                    'amount' => (float) $item->amount,
                    'description' => $item->description,
                    'notes' => $item->notes,
                    'is_estimated_date' => (bool) $item->is_estimated_date,
                ];
            })->values(),
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

        return Inertia::render('CashflowProjection/Settings', [
            'year' => $year,
            'selectedMonth' => $selectedMonth,
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
        ]);
    }

    public function storeLineItem(StoreCashflowProjectionLineItemRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->canManage($user, $businessUnitId), 403);

        $department = Department::query()
            ->where('business_unit_id', $businessUnitId)
            ->where('is_active', true)
            ->findOrFail((int) $request->integer('department_id'));

        abort_unless($this->userAssignedToDepartment($user, $businessUnitId, $department->id), 403);

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

        $cycle = $this->findOrCreateCycle($businessUnitId, (int) $request->integer('year'), $user->id);

        CashflowProjectionLineItem::query()->create([
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

        $transactionMonth = (int) ($request->date('transaction_date')?->format('n') ?? 0);
        $redirectParams = ['year' => $cycle->year];
        if ($transactionMonth >= 1 && $transactionMonth <= 12) {
            $redirectParams['month'] = $transactionMonth;
        }

        return redirect()
            ->route('cashflow-projection.entries', $redirectParams)
            ->with('success', 'Line item cashflow berhasil disimpan.');
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

        $financeMonth = (int) $request->integer('month');
        $redirectParams = ['year' => $cycle->year];
        if ($financeMonth >= 1 && $financeMonth <= 12) {
            $redirectParams['month'] = $financeMonth;
        }

        return redirect()
            ->route('cashflow-projection.settings', $redirectParams)
            ->with('success', 'Input finance cashflow berhasil disimpan.');
    }

    public function export(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $businessUnitId = (int) session('current_business_unit_id');

        abort_unless($this->accessService->isFinanceUser($user, $businessUnitId), 403);

        $year = (int) $request->integer('year', (int) now()->format('Y'));
        $selectedMonth = (int) max(1, min(12, $request->integer('month', (int) now()->format('n'))));

        $cycle = $this->findOrCreateCycle($businessUnitId, $year, $user->id);

        $assignments = $user->activeBusinessUnits()
            ->with(['department', 'position'])
            ->where('business_unit_id', $businessUnitId)
            ->get();

        $departmentIds = $assignments
            ->pluck('department')
            ->filter(fn ($department) => $department instanceof Department && $department->is_active)
            ->pluck('id');

        $lineItems = CashflowProjectionLineItem::query()
            ->with('department')
            ->where('cycle_id', $cycle->id)
            ->whereIn('department_id', $departmentIds)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $financeInputs = $this->userHasFinanceAssignment($user, $businessUnitId)
            ? CashflowProjectionFinanceInput::query()
                ->where('cycle_id', $cycle->id)
                ->orderBy('month')
                ->get()
            : collect();

        $dailySummary = $this->buildDailySummary($lineItems, $selectedMonth);
        $monthlySummary = $this->buildMonthlySummary($lineItems, $financeInputs);

        $filename = 'cashflow-projection-'.$year.'-m'.$selectedMonth.'.xls';

        return response()->streamDownload(function () use ($year, $selectedMonth, $monthlySummary, $dailySummary) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
            echo '<head><meta charset="UTF-8"></head><body>';

            echo '<table border="1">';
            echo '<tr><td colspan="8" style="font-weight:bold;font-size:14pt;">Cashflow Projection Export</td></tr>';
            echo '<tr><td colspan="8">Year: '.$year.' | Month: '.$selectedMonth.'</td></tr>';
            echo '<tr><td colspan="8"></td></tr>';

            echo '<tr style="background-color:#f0f0f0;font-weight:bold;">';
            echo '<td colspan="8">Monthly Summary</td>';
            echo '</tr>';
            echo '<tr style="font-weight:bold;">';
            echo '<td>Month</td><td>Plus</td><td>Minus</td><td>Finance Income</td><td>Opening Balance</td><td>Net</td><td>Closing Balance</td><td>Warning</td>';
            echo '</tr>';

            foreach ($monthlySummary as $row) {
                echo '<tr>';
                echo '<td>'.$row['month'].'</td>';
                echo '<td style="text-align:right;">'.number_format((float) $row['plus'], 0, '.', ',').'</td>';
                echo '<td style="text-align:right;">'.number_format((float) $row['minus'], 0, '.', ',').'</td>';
                echo '<td style="text-align:right;">'.number_format((float) $row['finance_income'], 0, '.', ',').'</td>';
                echo '<td style="text-align:right;">'.number_format((float) $row['opening_balance'], 0, '.', ',').'</td>';
                echo '<td style="text-align:right;">'.number_format((float) $row['net'], 0, '.', ',').'</td>';
                echo '<td style="text-align:right;">'.number_format((float) $row['closing_balance'], 0, '.', ',').'</td>';
                echo '<td>'.($row['is_warning'] ? 'YES' : 'NO').'</td>';
                echo '</tr>';
            }

            echo '<tr><td colspan="8"></td></tr>';
            echo '<tr style="background-color:#f0f0f0;font-weight:bold;"><td colspan="8">Daily Summary (Month '.$selectedMonth.')</td></tr>';
            echo '<tr style="font-weight:bold;"><td>Date</td><td>Plus</td><td>Minus</td><td>Net</td><td colspan="4"></td></tr>';

            foreach ($dailySummary as $row) {
                echo '<tr>';
                echo '<td>'.$row['date'].'</td>';
                echo '<td style="text-align:right;">'.number_format((float) $row['plus'], 0, '.', ',').'</td>';
                echo '<td style="text-align:right;">'.number_format((float) $row['minus'], 0, '.', ',').'</td>';
                echo '<td style="text-align:right;">'.number_format((float) $row['net'], 0, '.', ',').'</td>';
                echo '<td colspan="4"></td>';
                echo '</tr>';
            }

            echo '</table>';
            echo '</body></html>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
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
                'is_warning' => $closingBalance < self::MINIMUM_BALANCE_GLOBAL,
            ];
        }

        return $rows;
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
}
