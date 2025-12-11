<?php

namespace Database\Seeders;

use App\Models\Modules\Purchasing\PurchaseRequest\PrCategory;
use Illuminate\Database\Seeder;

class PrCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Operational',
                'code' => 'OPS',
                'description' => 'Day-to-day operational expenses such as office supplies, utilities, and maintenance',
                'color' => 'blue',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Project',
                'code' => 'PRJ',
                'description' => 'Project-related expenses for specific initiatives or deliverables',
                'color' => 'purple',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Marketing and promotional activities, advertising, and brand development',
                'color' => 'green',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Charity',
                'code' => 'CHR',
                'description' => 'Charitable donations, CSR activities, and community support programs',
                'color' => 'pink',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            PrCategory::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }

        $this->command->info('PR Categories seeded successfully: '.count($categories).' categories');
    }
}
