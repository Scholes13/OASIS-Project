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
 * Seeder untuk user baru Kayana di WNS / Sales & Marketing / CMC.
 *
 * Konteks: PRD 2026-05-25 WNS Restructure.
 * Kayana adalah satu-satunya user benar-benar baru di struktur 2026.
 * User lain di mapping migration sudah existing di DB (lihat section 04).
 *
 * Idempotent: aman dijalankan ulang via `firstOrCreate`.
 */
class WNSKayanaUserSeeder extends Seeder
{
    public function run(): void
    {
        $wns = BusinessUnit::where('code', 'WNS')->first();

        if (! $wns) {
            $this->command->error('Business Unit WNS tidak ditemukan. Jalankan BusinessUnitSeeder dulu.');

            return;
        }

        // CMC sub-department (parent: SM). Harus sudah di-seed via WNSSalesMarketingSeeder.
        $cmc = Department::where('business_unit_id', $wns->id)
            ->where('code', 'CMC')
            ->first();

        if (! $cmc) {
            $this->command->error('Department WNS/CMC tidak ditemukan. Jalankan WNSSalesMarketingSeeder dulu.');

            return;
        }

        // Position ANL_CMC harus sudah di-seed via WNSSalesMarketingPositionSeeder.
        $position = Position::where('department_id', $cmc->id)
            ->where('code', 'ANL_CMC')
            ->first();

        if (! $position) {
            $this->command->error('Position ANL_CMC di CMC tidak ditemukan. Jalankan WNSSalesMarketingPositionSeeder dulu.');

            return;
        }

        $email = 'abhi@werkudara.com';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'I.D.A. Kayana Abhipraya P.B.',
                'password' => Hash::make('werkudara88'),
                'global_role' => 'user',
                'primary_department_id' => $cmc->id,
                'primary_position_id' => $position->id,
                'is_active' => true,
            ]
        );

        // Assignment WNS / CMC, primary
        UserBusinessUnit::firstOrCreate(
            [
                'user_id' => $user->id,
                'business_unit_id' => $wns->id,
                'department_id' => $cmc->id,
            ],
            [
                'position_id' => $position->id,
                'is_primary' => true,
                'is_active' => true,
            ]
        );

        $this->command->info("Kayana seeded ({$email}) -> WNS/SM/CMC as Market Analyst.");
    }
}
