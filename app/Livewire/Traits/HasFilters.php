<?php

namespace App\Livewire\Traits;

use Livewire\WithPagination;

/**
 * HasFilters Trait
 *
 * Provides filter functionality for Livewire list components.
 * Automatically resets pagination when filters change.
 *
 * @example
 * class ProductList extends Component {
 *     use HasFilters, WithPagination;
 *
 *     #[Computed]
 *     public function items() {
 *         return Product::query()
 *             ->when($this->filters['search'] ?? null, fn($q, $search) =>
 *                 $q->where('name', 'like', "%{$search}%")
 *             )
 *             ->when($this->filters['status'] ?? null, fn($q, $status) =>
 *                 $q->where('status', $status)
 *             )
 *             ->paginate(20);
 *     }
 * }
 *
 * @view
 * <input
 *     wire:model.live.debounce.300ms="filters.search"
 *     wire:loading.attr="disabled"
 *     wire:target="applyFilters"
 * >
 * <button wire:click="resetFilters">Clear</button>
 */
trait HasFilters
{
    /**
     * Filter values
     */
    public array $filters = [];

    /**
     * Apply current filters
     * Resets pagination to first page
     */
    public function applyFilters(): void
    {
        // Reset to page 1 when filters change
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }

        // Trigger component refresh
        $this->dispatch('filters-applied');
    }

    /**
     * Reset all filters to default
     */
    public function resetFilters(): void
    {
        $this->filters = [];

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }

        $this->dispatch('filters-reset');
    }

    /**
     * Clear specific filter
     *
     * @param  string  $key  Filter key to clear
     */
    public function clearFilter(string $key): void
    {
        unset($this->filters[$key]);

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }

        $this->dispatch('filter-cleared', key: $key);
    }

    /**
     * Set multiple filters at once
     */
    public function setFilters(array $filters): void
    {
        $this->filters = array_merge($this->filters, $filters);
        $this->applyFilters();
    }

    /**
     * Check if any filter is active
     */
    public function hasActiveFilters(): bool
    {
        return ! empty(array_filter($this->filters));
    }

    /**
     * Get active filter count
     */
    public function getActiveFilterCount(): int
    {
        return count(array_filter($this->filters));
    }
}
