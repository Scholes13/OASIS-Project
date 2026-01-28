<?php

namespace App\Console\Commands;

use App\Services\Modules\Activity\BackdatePermissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireBackdatePermissions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'backdate:expire-permissions';

    /**
     * The console command description.
     */
    protected $description = 'Expire backdate permissions where granted_until has passed';

    /**
     * Execute the console command.
     */
    public function handle(BackdatePermissionService $backdateService): int
    {
        $this->info('Checking for expired backdate permissions...');

        try {
            $expiredCount = $backdateService->expireOldPermissions();

            $this->info("Expired {$expiredCount} backdate permission(s).");

            Log::info('Backdate permissions expiration completed', [
                'expired_count' => $expiredCount,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to expire backdate permissions: {$e->getMessage()}");

            Log::error('Failed to expire backdate permissions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
