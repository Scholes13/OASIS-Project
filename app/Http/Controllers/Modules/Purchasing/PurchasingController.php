<?php

namespace App\Http\Controllers\Modules\Purchasing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchasing\AllPurchasingRequestsRequest;
use App\Models\Core\Department;
use App\Services\Modules\Purchasing\AllRequests\AllPurchasingRequestsQueryService;
use App\Services\Modules\Purchasing\AllRequests\PurchasingRequestScopeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PurchasingController extends Controller
{
    public function __construct(
        private AllPurchasingRequestsQueryService $queryService,
        private PurchasingRequestScopeResolver $scopeResolver,
    ) {}

    public function allRequests(AllPurchasingRequestsRequest $request): Response
    {
        $user = Auth::user();
        $businessUnitId = (int) session('current_business_unit_id');

        $businessUnitIds = $this->scopeResolver->businessUnitIds($user, $businessUnitId);
        abort_if($businessUnitIds === [], 403);

        return Inertia::render('Purchasing/AllRequests', [
            'requests' => $this->queryService->paginate($request, $businessUnitIds),
            'filters' => $request->only([
                'search', 'type', 'status', 'department_id', 'date_from', 'date_to', 'sort', 'direction', 'per_page',
            ]),
            'departments' => Department::query()
                ->with('businessUnit:id,code')
                ->whereIn('business_unit_id', $businessUnitIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'business_unit_id', 'name', 'code']),
        ]);
    }

    public function redirectLegacyAllRequests(Request $request): RedirectResponse
    {
        return redirect()->route('purchasing.all-requests', $request->query());
    }
}
