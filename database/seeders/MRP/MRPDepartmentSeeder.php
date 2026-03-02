<?php

namespace Database\Seeders\MRP;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk departemen Maharaja Pratama (MRP)
 */
class MRPDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mrp = BusinessUnit::where('code', 'MRP')->first();

        if (! $mrp) {
            $this->command->error('Business Unit MRP tidak ditemukan!');

            return;
        }

        $departments = [
            ['code' => 'CT', 'name' => 'Corporate Travel'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                [
                    'business_unit_id' => $mrp->id,
                    'code' => $dept['code'],
                ],
                [
                    'name' => $dept['name'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info("MRP: {$mrp->name} - ".count($departments).' departemen berhasil di-seed.');
    }
}
