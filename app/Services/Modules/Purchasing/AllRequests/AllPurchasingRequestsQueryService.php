<?php

namespace App\Services\Modules\Purchasing\AllRequests;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllPurchasingRequestsQueryService
{
    /** @param array<int, int> $businessUnitIds */
    public function paginate(Request $request, array $businessUnitIds): array
    {
        $query = DB::query()->fromSub(
            $this->purchaseRequests($businessUnitIds)->unionAll($this->stockRequests($businessUnitIds)),
            'purchasing_requests',
        );

        $this->applyFilters($query, $request);

        $sort = in_array($request->string('sort')->toString(), ['created_at', 'date_of_request', 'number', 'status', 'total_amount'], true)
            ? $request->string('sort')->toString()
            : 'created_at';
        $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';
        $perPage = in_array($request->integer('per_page'), [15, 25, 50], true)
            ? $request->integer('per_page')
            : 15;
        $page = max(1, $request->integer('page', 1));
        $total = (clone $query)->count();

        $items = $query
            ->orderBy($sort, $direction)
            ->orderBy('type')
            ->orderBy('id', $direction)
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (object $item): array => $this->transform($item));

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return [
            'data' => $paginator->items(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'links' => $paginator->linkCollection()->all(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /** @param array<int, int> $businessUnitIds */
    private function purchaseRequests(array $businessUnitIds): Builder
    {
        return DB::table('purchase_requests as request')
            ->join('business_units as business_unit', 'business_unit.id', '=', 'request.business_unit_id')
            ->join('departments as department', 'department.id', '=', 'request.department_id')
            ->join('users as requester', 'requester.id', '=', 'request.user_id')
            ->leftJoinSub(
                DB::table('pr_items')->selectRaw('purchase_request_id, COUNT(*) as items_count')->groupBy('purchase_request_id'),
                'item_totals',
                'item_totals.purchase_request_id',
                '=',
                'request.id',
            )
            ->whereIn('request.business_unit_id', $businessUnitIds)
            ->select([
                DB::raw("'purchase_request' as type"),
                'request.id',
                'request.pr_number as number',
                'request.used_for as summary',
                'request.status',
                'request.date_of_request',
                'request.created_at',
                'request.business_unit_id',
                'business_unit.code as business_unit_code',
                'business_unit.name as business_unit_name',
                'request.department_id',
                'department.code as department_code',
                'department.name as department_name',
                'request.user_id',
                'requester.name as requester_name',
                DB::raw('COALESCE(item_totals.items_count, 0) as items_count'),
                'request.total_amount',
                'request.currency',
            ]);
    }

    /** @param array<int, int> $businessUnitIds */
    private function stockRequests(array $businessUnitIds): Builder
    {
        return DB::table('stock_requests as request')
            ->join('business_units as business_unit', 'business_unit.id', '=', 'request.business_unit_id')
            ->join('departments as department', 'department.id', '=', 'request.department_id')
            ->join('users as requester', 'requester.id', '=', 'request.user_id')
            ->leftJoinSub(
                DB::table('stock_items')
                    ->selectRaw('stock_request_id, COUNT(*) as items_count, COALESCE(SUM(total), 0) as total_amount')
                    ->groupBy('stock_request_id'),
                'item_totals',
                'item_totals.stock_request_id',
                '=',
                'request.id',
            )
            ->whereIn('request.business_unit_id', $businessUnitIds)
            ->select([
                DB::raw("'stock_request' as type"),
                'request.id',
                'request.st_number as number',
                'request.purpose as summary',
                'request.status',
                'request.date_of_request',
                'request.created_at',
                'request.business_unit_id',
                'business_unit.code as business_unit_code',
                'business_unit.name as business_unit_name',
                'request.department_id',
                'department.code as department_code',
                'department.name as department_name',
                'request.user_id',
                'requester.name as requester_name',
                DB::raw('COALESCE(item_totals.items_count, 0) as items_count'),
                DB::raw('COALESCE(item_totals.total_amount, 0) as total_amount'),
                DB::raw("'IDR' as currency"),
            ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($search = trim($request->string('search')->toString())) {
            $query->where(fn (Builder $nested) => $nested
                ->where('number', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%")
                ->orWhere('requester_name', 'like', "%{$search}%"));
        }

        if (in_array($request->string('type')->toString(), ['purchase_request', 'stock_request'], true)) {
            $query->where('type', $request->string('type')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->integer('department_id') > 0) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date_of_request', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_of_request', '<=', $request->date('date_to'));
        }
    }

    private function transform(object $item): array
    {
        $data = (array) $item;
        $data['id'] = (int) $item->id;
        $data['business_unit_id'] = (int) $item->business_unit_id;
        $data['department_id'] = (int) $item->department_id;
        $data['user_id'] = (int) $item->user_id;
        $data['items_count'] = (int) $item->items_count;
        $data['total_amount'] = (string) $item->total_amount;
        $data['show_url'] = $item->type === 'purchase_request'
            ? route('purchase-requests.show', ['purchaseRequest' => $item->id])
            : route('stock-requests.show', ['stockRequest' => $item->id]);

        return $data;
    }
}
