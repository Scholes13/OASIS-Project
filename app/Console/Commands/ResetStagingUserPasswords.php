<?php

namespace App\Console\Commands;

use App\Models\Core\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetStagingUserPasswords extends Command
{
    protected $signature = 'staging:reset-user-passwords {--password=werkudara88}';

    protected $description = 'Reset all user passwords in the staging environment';

    public function handle(): int
    {
        if (! app()->environment('staging')) {
            $this->error('This command is only allowed when APP_ENV=staging.');

            return Command::FAILURE;
        }

        $password = (string) $this->option('password');

        if ($password === '') {
            $this->error('Password cannot be empty.');

            return Command::FAILURE;
        }

        $count = User::query()->update([
            'password' => Hash::make($password),
        ]);

        $this->info("Reset {$count} staging user password(s).");

        return Command::SUCCESS;
    }
}
