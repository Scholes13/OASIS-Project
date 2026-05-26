<?php

namespace Database\Seeders\WNS;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder for new user Etik Andriyanti (GM Sales & Marketing).
 *
 * Context: PRD 2026-05-25 WNS Restructure.
 * Etik is one of two new users introduced by the 2026 structure
 * (the other is I.D.A. Kayana, see WNSKayanaUserSeeder).
 *
 * Idempotent via firstOrCreate.
 *
 * Prerequisites:
 *   - WNSSalesMarketingSeeder (creates SM dept)
 *   - WNSSalesMarketingPositionSeeder (creates GM_SM position)
 */
class WNSEtikUserSeeder extends Seeder
{
    public function run(): void
    {
        $wns = BusinessUnit::where('code', 'WNS')->first();

        if (! $wns) {
            $this->command->error('Business Unit WNS not found. Run BusinessUnitSeeder first.');

            return;
        }

        $sm = Department::where('business_unit_id', $wns->id)
            ->where('code', 'SM')
            ->first();

        if (! $sm) {
            $this->command->error('Department WNS/SM not found. Run WNSSalesMarketingSeeder first.');

            return;
        }

        $position = Position::where('department_id', $sm->id)
            ->where('code', 'GM_SM')
            ->first();

        if (! $position) {
            $this->command->error('Position GM_SM in SM not found. Run WNSSalesMarketingPositionSeeder first.');

            return;
        }

        $email = 'andri@werkudara.com';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Etik Andriyanti',
                'password' => Hash::make('werkudara88'),
                'global_role' => 'user',
                'primary_department_id' => $sm->id,
                'primary_position_id' => $position->id,
                'is_active' => true,
            ]
        );

        UserBusinessUnit::firstOrCreate(
            [
                'user_id' => $user->id,
                'business_unit_id' => $wns->id,
                'department_id' => $sm->id,
            ],
            [
                'position_id' => $position->id,
                'is_primary' => true,
                'is_active' => true,
            ]
        );

        $this->command->info("Etik seeded ({$email}) -> WNS/SM as General Manager.");
    }
}
