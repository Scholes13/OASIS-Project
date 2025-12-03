<?php

namespace App\Livewire\Modules\PurchaseRequest;

use App\Models\Modules\PurchaseRequest\PrCategory;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Lazy]
class AllRequests extends Component
{
    use WithPagination;

    #[Url]
    public ?string $category = null;

    #[Url]
    public bool $showAll = false;

    public array $categories = [];

    public array $categoryStats = [];

    public int $totalPRs = 0;

    public int $currentBusinessUnitId = 0;

    public string $currentBusinessUnitName = '';

    public function mount(): void
    {
        $this->loadData();
    }

    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId = null): void
    {
        // Update from session (single source of truth)
        $this->currentBusinessUnitId = (int) session('current_business_unit_id');
        $this->currentBusinessUnitName = session('current_business_unit_name') ?? '';

        // Reset filters
        $this->category = null;
        $this->showAll = false;
        $this->resetPage();

        // Reload data
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->currentBusinessUnitId = (int) session('current_business_unit_id');
        $this->currentBusinessUnitName = session('current_business_unit_name') ?? '';

        // Verify access
        $user = Auth::user();
        $userBusinessUnitIds = $user->activeBusinessUnits()->pluck('business_unit_id')->toArray();

        if (! $this->currentBusinessUnitId || ! in_array($this->currentBusinessUnitId, $userBusinessUnitIds)) {
            $this->redirect(route('purchase-requests.index'));

            return;
        }

        // Load categories
        $this->categories = PrCategory::active()->ordered()->get()->toArray();

        // Calculate stats per category
        $this->categoryStats = [];
        foreach ($this->categories as $cat) {
            $this->categoryStats[$cat['id']] = PurchaseRequest::where('business_unit_id', $this->currentBusinessUnitId)
                ->where('category_id', $cat['id'])
                ->count();
        }

        // Total PRs
        $this->totalPRs = PurchaseRequest::where('business_unit_id', $this->currentBusinessUnitId)->count();
    }

    public function filterByCategory(?int $categoryId): void
    {
        $this->category = $categoryId ? (string) $categoryId : null;
        $this->showAll = false;
        $this->resetPage();
    }

    public function showAllDocuments(): void
    {
        $this->category = null;
        $this->showAll = true;
        $this->resetPage();
    }

    public function clearFilter(): void
    {
        $this->category = null;
        $this->showAll = false;
        $this->resetPage();
    }

    public function placeholder(): string
    {
        return <<<'HTML'
        <div class="min-h-screen bg-white">
            <div class="w-full">
                <div class="border-b border-gray-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="h-8 w-64 bg-gray-200 rounded animate-pulse"></div>
                            <div class="h-4 w-48 bg-gray-100 rounded mt-2 animate-pulse"></div>
                        </div>
                    </div>
                </div>
                <div class="border-b border-gray-200 px-6 py-8">
                    <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 1.5rem;">
                        <div class="px-6 py-5 rounded-lg border border-gray-200 bg-white animate-pulse">
                            <div class="flex items-center">
                                <div class="w-1 h-16 bg-gray-200 rounded-full mr-4"></div>
                                <div>
                                    <div class="h-8 w-12 bg-gray-200 rounded"></div>
                                    <div class="h-4 w-20 bg-gray-100 rounded mt-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-5 rounded-lg border border-gray-200 bg-white animate-pulse">
                            <div class="flex items-center">
                                <div class="w-1 h-16 bg-gray-200 rounded-full mr-4"></div>
                                <div>
                                    <div class="h-8 w-12 bg-gray-200 rounded"></div>
                                    <div class="h-4 w-20 bg-gray-100 rounded mt-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-5 rounded-lg border border-gray-200 bg-white animate-pulse">
                            <div class="flex items-center">
                                <div class="w-1 h-16 bg-gray-200 rounded-full mr-4"></div>
                                <div>
                                    <div class="h-8 w-12 bg-gray-200 rounded"></div>
                                    <div class="h-4 w-20 bg-gray-100 rounded mt-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-5 rounded-lg border border-gray-200 bg-white animate-pulse">
                            <div class="flex items-center">
                                <div class="w-1 h-16 bg-gray-200 rounded-full mr-4"></div>
                                <div>
                                    <div class="h-8 w-12 bg-gray-200 rounded"></div>
                                    <div class="h-4 w-20 bg-gray-100 rounded mt-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-5 rounded-lg border border-gray-200 bg-white animate-pulse">
                            <div class="flex items-center">
                                <div class="w-1 h-16 bg-gray-200 rounded-full mr-4"></div>
                                <div>
                                    <div class="h-8 w-12 bg-gray-200 rounded"></div>
                                    <div class="h-4 w-20 bg-gray-100 rounded mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4">
                    <div class="animate-pulse space-y-4">
                        <div class="h-10 bg-gray-100 rounded"></div>
                        <div class="h-16 bg-gray-50 rounded"></div>
                        <div class="h-16 bg-gray-50 rounded"></div>
                        <div class="h-16 bg-gray-50 rounded"></div>
                        <div class="h-16 bg-gray-50 rounded"></div>
                        <div class="h-16 bg-gray-50 rounded"></div>
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }

    public function render()
    {
        $purchaseRequests = PurchaseRequest::with(['department', 'user', 'items', 'approvals', 'category'])
            ->where('business_unit_id', $this->currentBusinessUnitId)
            ->when($this->category, fn ($q) => $q->where('category_id', $this->category))
            ->latest('created_at')
            ->paginate(10);

        return view('livewire.modules.purchase-request.all-requests', [
            'purchaseRequests' => $purchaseRequests,
        ]);
    }
}
