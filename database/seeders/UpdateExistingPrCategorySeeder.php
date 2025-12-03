<?php

namespace Database\Seeders;

use App\Models\Modules\PurchaseRequest\PrCategory;
use App\Models\Modules\PurchaseRequest\PurchaseRequest;
use Illuminate\Database\Seeder;

class UpdateExistingPrCategorySeeder extends Seeder
{
    /**
     * Update all existing Purchase Requests to have "Operational" category.
     */
    public function run(): void
    {
        // Get the Operational category
        $operationalCategory = PrCategory::where('code', 'OPS')->first();

        if (! $operationalCategory) {
            $this->command->error('Operational category not found. Please run PrCategorySeeder first.');

            return;
        }

        // Update all PRs that don't have a category
        $updated = PurchaseRequest::whereNull('category_id')
            ->update(['category_id' => $operationalCategory->id]);

        $this->command->info("Updated {$updated} Purchase Requests to Operational category.");
    }
}
