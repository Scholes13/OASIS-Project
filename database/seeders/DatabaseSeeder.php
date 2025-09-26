<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BusinessUnitSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            UserSeeder::class,
            NumberingModuleSeeder::class,
            ApprovalWorkflowSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
    }
}
