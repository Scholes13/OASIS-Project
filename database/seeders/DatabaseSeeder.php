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
        // Run seeders in dependency order
        $this->call([
            BusinessUnitSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            UserSeeder::class,
            WerkudaraGroupSeeder::class, // Add after UserSeeder to update existing data
            NumberingModuleSeeder::class,
            ApprovalWorkflowSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
    }
}
