<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\UniversalPRNumberingService;
use Carbon\Carbon;

class TestCorrectPRGeneration extends Command
{
    protected $signature = 'test:correct-pr {user_id=3}';
    protected $description = 'Test PR generation with correct business unit logic';

    public function handle()
    {
        $userId = $this->argument('user_id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User {$userId} not found");
            return;
        }
        
        $this->info("Testing PR generation for: {$user->name}");
        $this->info("User's primary BU: " . ($user->primaryDepartment ? $user->primaryDepartment->businessUnit->code : 'None'));
        
        // Simulate middleware setting session
        if ($user->global_role === 'super_admin' && $user->primaryDepartment) {
            $primaryBu = $user->primaryDepartment->businessUnit;
            session([
                'current_business_unit_id' => $primaryBu->id,
                'current_business_unit_code' => $primaryBu->code,
                'current_business_unit_name' => $primaryBu->name,
                'current_user_role' => 'super_admin',
                'current_department_id' => $user->primaryDepartment->id,
            ]);
            
            $this->info("Session set to: {$primaryBu->code} - {$primaryBu->name}");
        }
        
        try {
            $service = app(UniversalPRNumberingService::class);
            
            // Test with session business unit (should be WG for super admin)
            $result = $service->generatePRNumber($user, null, null, Carbon::today());
            
            $this->info("✓ Generated PR Number: {$result['formatted_number']}");
            $this->info("  Business Unit: {$result['business_unit_code']} - {$result['business_unit_name']}");
            $this->info("  Department: {$result['department_code']} - {$result['department_name']}");
            
            // Verify it's using the correct business unit
            if ($result['business_unit_code'] === 'WG') {
                $this->info("✓ Correctly using WG (Werkudara Group) for super admin");
            } else {
                $this->error("✗ Should be using WG, but got: {$result['business_unit_code']}");
            }
            
        } catch (\Exception $e) {
            $this->error("✗ Error: " . $e->getMessage());
        }
    }
}