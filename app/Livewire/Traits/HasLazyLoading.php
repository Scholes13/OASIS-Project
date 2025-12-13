<?php

namespace App\Livewire\Traits;

/**
 * HasLazyLoading Trait
 *
 * Provides lazy loading functionality for Livewire components.
 * Use this trait to defer data loading until component is visible,
 * improving initial page load performance.
 *
 * @example
 * class MyComponent extends Component {
 *     use HasLazyLoading;
 *
 *     #[Computed]
 *     public function items() {
 *         if (!$this->readyToLoad) return collect();
 *         return Model::query()->get();
 *     }
 * }
 *
 * @view
 * <div wire:init="loadData">
 *
 *     @if($readyToLoad)
 *
 *         @foreach($this->items as $item)
 *             <!-- content -->
 *
 *         @endforeach
 *
 *     @else
 *         <x-loading-skeleton />
 *
 *     @endif
 * </div>
 */
trait HasLazyLoading
{
    /**
     * Indicates if component is ready to load data
     */
    public bool $readyToLoad = false;

    /**
     * Trigger data loading
     * Called via wire:init in view
     */
    public function loadData(): void
    {
        $this->readyToLoad = true;
    }

    /**
     * Reset lazy loading state
     * Useful for re-triggering load after filters change
     */
    public function resetLazyLoad(): void
    {
        $this->readyToLoad = false;
        // Immediately re-enable loading so data refreshes
        $this->readyToLoad = true;
    }
}
