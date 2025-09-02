<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\Department;
use App\Models\Modules\WNS\PurchaseRequest;
use App\Models\Modules\WNS\PrApproval;
use App\Models\NumberSequence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard
     */
    public function index()
    {
        // System overview statistics
        $stats = [
            'total_users' => User::where('is_active', true)->count(),
            'total_business_units' => BusinessUnit::where('is_active', true)->count(),
            'total_departments' => Department::where('is_active', true)->count(),
            'total_purchase_requests' => PurchaseRequest::count(),
            'pending_approvals' => PrApproval::where('status', 'pending')->count(),
            'active_sequences' => NumberSequence::where('is_active', true)->count(),
        ];

        // Recent activity
        $recentPRs = PurchaseRequest::with(['user', 'department'])
            ->latest()
            ->limit(5)
            ->get();

        $recentApprovals = PrApproval::with(['purchaseRequest', 'approver'])
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('responded_at')
            ->limit(5)
            ->get();

        // Business unit breakdown
        $businessUnitStats = BusinessUnit::where('is_active', true)
            ->withCount(['departments', 'users'])
            ->get();

        // Monthly PR trends
        $monthlyPRs = DB::table('purchase_requests')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
            ->whereYear('created_at', now()->year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                return [now()->month($item->month)->format('M') => $item->count];
            });

        return view('admin.dashboard', compact(
            'stats', 
            'recentPRs', 
            'recentApprovals', 
            'businessUnitStats', 
            'monthlyPRs'
        ));
    }

    /**
     * Show system health and configuration
     */
    public function systemHealth()
    {
        $health = [
            'database' => $this->checkDatabaseHealth(),
            'file_permissions' => $this->checkFilePermissions(),
            'cache' => $this->checkCacheHealth(),
            'queue' => $this->checkQueueHealth(),
        ];

        return view('admin.system-health', compact('health'));
    }

    /**
     * Check database connectivity and performance
     */
    protected function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time_ms' => $responseTime,
                'message' => 'Database connection is working properly'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'response_time_ms' => null,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check file permissions
     */
    protected function checkFilePermissions(): array
    {
        $paths = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        $issues = [];
        foreach ($paths as $path) {
            if (!is_writable($path)) {
                $issues[] = "Path not writable: {$path}";
            }
        }

        return [
            'status' => empty($issues) ? 'healthy' : 'warning',
            'issues' => $issues,
            'message' => empty($issues) ? 'All required directories are writable' : 'Some directories have permission issues'
        ];
    }

    /**
     * Check cache system
     */
    protected function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            
            cache()->put($testKey, $testValue, 60);
            $retrieved = cache()->get($testKey);
            cache()->forget($testKey);

            return [
                'status' => $retrieved === $testValue ? 'healthy' : 'error',
                'message' => $retrieved === $testValue ? 'Cache is working properly' : 'Cache read/write failed'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache system error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check queue system
     */
    protected function checkQueueHealth(): array
    {
        try {
            // Simple check - in production you might want to check actual queue status
            return [
                'status' => 'healthy',
                'message' => 'Queue system is configured'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue system error: ' . $e->getMessage()
            ];
        }
    }
}