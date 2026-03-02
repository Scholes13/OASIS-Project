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
 * Seeder untuk User/Karyawan WNS
 */
class WNSUserSeeder extends Seeder
{
    public function run(): void
    {
        $wns = BusinessUnit::where('code', 'WNS')->first();

        if (! $wns) {
            $this->command->error('Business Unit WNS tidak ditemukan!');

            return;
        }

        // Get all departments for WNS
        $departments = Department::where('business_unit_id', $wns->id)
            ->get()
            ->keyBy('code');

        // Get positions
        $positions = Position::all()->keyBy('name');

        $users = [
            ['name' => 'Brigitha Paramitha Maharesmi Nugraheni Putri', 'email' => 'mitha', 'dept' => 'PD', 'position' => 'Leader'],
            ['name' => 'Siman', 'email' => 'siman', 'dept' => 'GA', 'position' => 'Staff'],
            ['name' => 'Maria Dita Fivtiari', 'email' => 'dita', 'dept' => 'HR', 'position' => 'HOD'],
            ['name' => 'Chelsea Vida Erdanti', 'email' => 'chelsea', 'dept' => 'SO', 'position' => 'Staff'],
            ['name' => 'Meida Indra Lestianingsih', 'email' => 'meida', 'dept' => 'HR', 'position' => 'Staff'],
            ['name' => 'Abilio Mandala Putra', 'email' => 'abilio', 'dept' => 'ACS', 'position' => 'Staff'],
            ['name' => 'Sheilia Vania', 'email' => 'sheilia', 'dept' => 'GA', 'position' => 'Staff'],
            ['name' => 'Ruli Setyawan', 'email' => 'ruli', 'dept' => 'GA', 'position' => 'Staff'],
            ['name' => 'Muhammad Anwar', 'email' => 'anwar', 'dept' => 'SS', 'position' => 'Staff'],
            ['name' => 'Dwi Sakti Arsidatama', 'email' => 'sakti', 'dept' => 'CFC', 'position' => 'Staff'],
            ['name' => 'Yulia Eka Crystanti', 'email' => 'eka', 'dept' => 'BAS', 'position' => 'Staff'],
            ['name' => 'Setyorini Dewi Ismu Hardiyanto', 'email' => 'dewi', 'dept' => 'SS', 'position' => 'Staff'],
            ['name' => 'Amila Mayosa Sehati', 'email' => 'mila', 'dept' => 'SS', 'position' => 'Staff'],
            ['name' => 'Purwati Harjayani', 'email' => 'purwati', 'dept' => 'ACC', 'position' => 'Staff'],
            ['name' => 'Krisnanto Patriawanjati', 'email' => 'krisnanto', 'dept' => 'ACC', 'position' => 'HOD'],
            ['name' => 'Burhan Zudi Saputro', 'email' => 'zudi', 'dept' => 'GA', 'position' => 'Leader'],
            ['name' => 'Hanifah Abdillah', 'email' => 'hanifah', 'dept' => 'ACC', 'position' => 'Staff'],
            ['name' => 'Refangga', 'email' => 'refangga', 'dept' => 'ACS', 'position' => 'Staff'],
            ['name' => 'Wulida Tsania Hima Aulia', 'email' => 'tsania', 'dept' => 'PD', 'position' => 'Staff'],
            ['name' => 'Okki Putri Fadilah', 'email' => 'okki', 'dept' => 'CFC', 'position' => 'Staff'],
            ['name' => 'Fuad Jaka Pamungkas', 'email' => 'jaka', 'dept' => 'BID', 'position' => 'Leader'],
            ['name' => 'Pramuji Arif Yulianto', 'email' => 'pramuji', 'dept' => 'BAS', 'position' => 'Staff'],
            ['name' => 'Andrew Ardhany Sudharma', 'email' => 'andrew', 'dept' => 'ACS', 'position' => 'Staff'],
            ['name' => 'Sarah Nur Amalia', 'email' => 'sarah', 'dept' => 'ACS', 'position' => 'Staff'],
            ['name' => 'Muhammad Haekal Baihaqi', 'email' => 'haekal', 'dept' => 'SO', 'position' => 'Staff'],
            ['name' => 'Alif Faisal Abdurrahman', 'email' => 'alif', 'dept' => 'BID', 'position' => 'Staff'],
            ['name' => 'Vanessa Salvathea', 'email' => 'vanessa', 'dept' => 'SO', 'position' => 'Staff'],
            ['name' => 'F.A. Anggito Enggarjati', 'email' => 'enggar', 'dept' => 'PD', 'position' => 'Staff'],
            ['name' => 'Muhammad Zaky Al-Aqsa', 'email' => 'zaky', 'dept' => 'SO', 'position' => 'Staff'],
            ['name' => 'Bulqis Purnama Dewi', 'email' => 'bulqis', 'dept' => 'SO', 'position' => 'Staff'],
            ['name' => 'Septian Mahendra Dewantoro', 'email' => 'septian', 'dept' => 'ACS', 'position' => 'Staff'],
            ['name' => 'Rizqi Kurnia Himawan', 'email' => 'rizqi', 'dept' => 'HR', 'position' => 'Staff'],
            ['name' => 'Elfasa Khoirumansyah', 'email' => 'elfasa', 'dept' => 'TEP', 'position' => 'Staff'],
            ['name' => 'Ainur Hasanah', 'email' => 'ainur', 'dept' => 'TEP', 'position' => 'Staff'],
        ];

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($users as $userData) {
            $department = $departments->get($userData['dept']);
            if (! $department) {
                $this->command->warn("Department {$userData['dept']} tidak ditemukan, skip {$userData['name']}");
                $skipped++;

                continue;
            }

            // Find position by name in this department
            $position = Position::where('department_id', $department->id)
                ->where('name', $userData['position'])
                ->first();

            if (! $position) {
                // Try to find any position with similar name
                $position = Position::where('department_id', $department->id)->first();
                if (! $position) {
                    $this->command->warn("Position {$userData['position']} tidak ditemukan di {$userData['dept']}, skip {$userData['name']}");
                    $skipped++;

                    continue;
                }
            }

            // Use provided email (auto-append @werkudara.com if needed) or generate from name
            if (! empty($userData['email'])) {
                $email = str_contains($userData['email'], '@')
                    ? $userData['email']
                    : $userData['email'].'@werkudara.com';
            } else {
                $email = $this->generateEmail($userData['name']);
            }

            // Check if user already exists by name
            $existingUser = User::where('name', $userData['name'])->first();
            if ($existingUser) {
                // Check if email is already used by another user
                $emailTaken = User::where('email', $email)
                    ->where('id', '!=', $existingUser->id)
                    ->exists();

                if ($emailTaken) {
                    $this->command->warn("Email {$email} sudah dipakai user lain, skip update {$userData['name']}");
                    $skipped++;

                    continue;
                }

                // Update existing user's email and password
                $existingUser->update([
                    'email' => $email,
                    'password' => Hash::make('werkudara88'),
                ]);
                $this->command->info("User {$userData['name']} updated (email: {$email})");
                $updated++;

                continue;
            }

            // Check if email already exists for new user
            $emailExists = User::where('email', $email)->exists();
            if ($emailExists) {
                $this->command->warn("Email {$email} sudah ada, skip create {$userData['name']}");
                $skipped++;

                continue;
            }

            // Create user
            $user = User::create([
                'name' => $userData['name'],
                'email' => $email,
                'password' => Hash::make('werkudara88'),
                'global_role' => 'user',
                'primary_department_id' => $department->id,
                'primary_position_id' => $position->id,
                'is_active' => true,
            ]);

            // Assign to WNS business unit
            UserBusinessUnit::create([
                'user_id' => $user->id,
                'business_unit_id' => $wns->id,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'is_primary' => true,
                'is_active' => true,
            ]);

            $created++;
        }

        $this->command->info("WNS Users seeded: {$created} created, {$updated} updated, {$skipped} skipped");
    }

    /**
     * Generate email from name
     */
    private function generateEmail(string $name): string
    {
        // Convert to lowercase, replace spaces with dots
        $email = strtolower($name);
        $email = preg_replace('/[^a-z0-9\s]/', '', $email); // Remove special chars
        $email = preg_replace('/\s+/', '.', trim($email)); // Replace spaces with dots

        return $email.'@werkudara.com';
    }
}
