<?php

namespace App\Livewire\Modules\PurchaseRequest;

use App\Models\Modules\PurchaseRequest\PrCategory;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AllRequests extends Component
{
    use WithPagination;

    #[Url]
    public ?string $category = null;

    #[Url]
    public bool $showAll = false;

    #[Url]
    public string $search = '';

    // Lazy loading flag
    public bool $readyToLoad = false;

    public array $categories = [];

    public array $categoryStats = [];

    public int $totalPRs = 0;

    public int $currentBusinessUnitId = 0;

    public string $currentBusinessUnitName = '';

    /**
     * Lazy load data after component is mounted
     */
    public function loadData(): void
    {
        $this->readyToLoad = true;
        $this->initializeData();
    }

    protected function initializeData(): void
    {
        $this->currentBusinessUnitId = (int) session('current_business_unit_id');
        $this->currentBusinessUnitName = session('current_business_unit_name') ?? '';

        // Verify access using hierarchical method (consistent with BusinessUnitSwitcher)
        $user = Auth::user();
        $accessibleBusinessUnitIds = $user->getAccessibleBusinessUnitIds();

        if (! $this->currentBusinessUnitId || ! in_array($this->currentBusinessUnitId, $accessibleBusinessUnitIds)) {
            $this->redirect(route('purchase-requests.index'));
            return;
        }
        
        $this->loadCategoriesAndStats();
    }

    #[On('business-unit-switched')]
    public function handleBusinessUnitSwitch($businessUnitId = null): void
    {
        // ✅ FIX: Use parameter directly if provided, then session as fallback
        if ($businessUnitId) {
            $this->currentBusinessUnitId = (int) $businessUnitId;
            session(['current_business_unit_id' => $businessUnitId]);
        } else {
            $this->currentBusinessUnitId = (int) session('current_business_unit_id');
        }
        $this->currentBusinessUnitName = session('current_business_unit_name') ?? '';

        // Reset filters
        $this->category = null;
        $this->showAll = false;
        $this->search = '';
        $this->resetPage();

        // Reload data
        $this->loadCategoriesAndStats();

        // ✅ ORCHESTRATOR: Acknowledge completion
        $this->dispatch('bu-switch-acknowledge', component: 'all-requests');
        $this->dispatch('notify',
            message: "Switched to {$this->currentBusinessUnitName}",
            type: 'success'
        );
    }

    /**
     * Load categories and calculate stats
     */
    protected function loadCategoriesAndStats(): void
    {
        if (! $this->currentBusinessUnitId) {
            $this->categories = [];
            $this->categoryStats = [];
            $this->totalPRs = 0;
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
        $this->search = '';
        $this->resetPage();
    }

    public function clearFilter(): void
    {
        $this->category = null;
        $this->showAll = false;
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Go to a specific page
     */
    public function gotoPage(int $page): void
    {
        $this->setPage($page);
    }

    /**
     * Reset pagination when search changes
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // Return empty paginator if not ready (lazy loading)
        if (!$this->readyToLoad) {
            return view('livewire.modules.purchase-request.all-requests', [
                'purchaseRequests' => new LengthAwarePaginator([], 0, 10, 1),
            ]);
        }

        $query = PurchaseRequest::with(['department', 'user', 'items', 'approvals', 'category'])
            ->where('business_unit_id', $this->currentBusinessUnitId)
            ->when($this->category, fn ($q) => $q->where('category_id', $this->category));

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('pr_number', 'like', '%' . $this->search . '%')
                  ->orWhere('used_for', 'like', '%' . $this->search . '%');
            });
        }

        $purchaseRequests = $query->latest('created_at')->paginate(10);

        return view('livewire.modules.purchase-request.all-requests', [
            'purchaseRequests' => $purchaseRequests,
        ]);
    }
}
