<?php

namespace App\Console\Commands;

use App\Models\Modules\WNS\PurchaseRequest;
use App\Models\PrNumberReservation;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugPurchaseRequestHistory extends Command
{
    protected $signature = 'debug:pr-history {user_id?}';

    protected $description = 'Debug purchase request history visibility issues';

    public function handle()
    {
        $userId = $this->argument('user_id');

        if (! $userId) {
            $this->info('Available users:');
            $users = User::select('id', 'name', 'email', 'global_role')->get();
            foreach ($users as $user) {
                $this->line("ID: {$user->id} - {$user->name} ({$user->email}) - Role: {$user->global_role}");
            }
            $userId = $this->ask('Enter user ID to debug');
        }

        $user = User::find($userId);
        if (! $user) {
            $this->error("User with ID {$userId} not found");

            return;
        }

        $this->info("Debugging for user: {$user->name} ({$user->email})");
        $this->info("Global role: {$user->global_role}");
        $this->info("Primary department ID: {$user->primary_department_id}");

        // Check current business unit in session (simulate)
        $businessUnits = DB::table('business_units')->get();
        $this->info("\nAvailable business units:");
        foreach ($businessUnits as $bu) {
            $this->line("ID: {$bu->id} - {$bu->name} ({$bu->code})");
        }

        $businessUnitId = $this->ask('Enter business unit ID to simulate session');

        $this->info("\n=== PR NUMBER RESERVATIONS ===");
        $reservations = PrNumberReservation::where('user_id', $userId)
            ->where('business_unit_id', $businessUnitId)
            ->with(['businessUnit', 'department', 'purchaseRequest'])
            ->get();

        $this->info("Found {$reservations->count()} reservations for this user in business unit {$businessUnitId}");

        foreach ($reservations as $reservation) {
            $this->line("- {$reservation->pr_number} | Status: {$reservation->status} | Reserved: {$reservation->reserved_at}");
            if ($reservation->purchaseRequest) {
                $this->line("  ??? Connected to PR ID: {$reservation->purchaseRequest->id}");
            }
        }

        $this->info("\n=== PURCHASE REQUESTS ===");
        $purchaseRequests = PurchaseRequest::where('user_id', $userId)
            ->where('business_unit_id', $businessUnitId)
            ->with(['department', 'user', 'items'])
            ->get();

        $this->info("Found {$purchaseRequests->count()} purchase requests for this user in business unit {$businessUnitId}");

        foreach ($purchaseRequests as $pr) {
            $this->line("- {$pr->pr_number} | Status: {$pr->status} | Created: {$pr->created_at}");
            $deptName = $pr->department ? $pr->department->name : 'N/A';
            $this->line("  Department: {$deptName} | Items: {$pr->items->count()}");
        }

        // Check access level simulation
        $this->info("\n=== ACCESS LEVEL CHECK ===");
        $accessLevel = $user->getAccessLevel();
        $this->info("User access level: {$accessLevel}");

        // Simulate the controller query
        $this->info("\n=== CONTROLLER QUERY SIMULATION ===");
        $query = PurchaseRequest::with(['department', 'user', 'items'])
            ->where('business_unit_id', $businessUnitId);

        switch ($accessLevel) {
            case 'super_admin':
                $this->info('Super admin - can see all PRs in business unit');
                break;

            case 'executive':
            case 'general_manager':
                $this->info('Executive/GM - can see all PRs in business unit');
                break;

            case 'department_head':
                $this->info("Department head - filtering by department: {$user->primary_department_id}");
                $query->where('department_id', $user->primary_department_id);
                break;

            case 'team_leader':
                $subordinateIds = $user->activeSubordinates()->pluck('id')->toArray();
                $subordinateIds[] = $user->id;
                $this->info('Team leader - filtering by user IDs: '.implode(', ', $subordinateIds));
                $query->whereIn('user_id', $subordinateIds);
                break;

            case 'staff':
            default:
                $this->info("Staff - filtering by user ID: {$user->id}");
                $query->byUser($user->id);
                break;
        }

        $filteredPRs = $query->get();
        $this->info("Query result: {$filteredPRs->count()} purchase requests");

        foreach ($filteredPRs as $pr) {
            $deptName = $pr->department ? $pr->department->name : 'N/A';
            $this->line("- {$pr->pr_number} | User: {$pr->user->name} | Dept: {$deptName}");
        }

        // Check for data inconsistencies
        $this->info("\n=== DATA CONSISTENCY CHECK ===");

        // Check if there are reservations without corresponding PRs
        $unusedReservations = $reservations->where('status', 'used')->whereNull('purchase_request_id');
        if ($unusedReservations->count() > 0) {
            $this->warn("Found {$unusedReservations->count()} reservations marked as 'used' but without purchase_request_id:");
            foreach ($unusedReservations as $res) {
                $this->line("- {$res->pr_number}");
            }
        }

        // Check if there are PRs without corresponding reservations
        $prNumbers = $purchaseRequests->pluck('pr_number');
        $reservationNumbers = $reservations->pluck('pr_number');
        $orphanedPRs = $prNumbers->diff($reservationNumbers);

        if ($orphanedPRs->count() > 0) {
            $this->warn("Found {$orphanedPRs->count()} PRs without corresponding reservations:");
            foreach ($orphanedPRs as $prNumber) {
                $this->line("- {$prNumber}");
            }
        }
    }
}
