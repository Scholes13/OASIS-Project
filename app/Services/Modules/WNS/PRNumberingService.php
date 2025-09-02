<?php

namespace App\Services\Modules\WNS;

use App\Services\NumberingService;
use App\Models\Modules\WNS\PurchaseRequest;
use App\Models\User;
use App\Models\BusinessUnit;
use App\Models\NumberingModule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PRNumberingService
{
    protected NumberingService $numberingService;
    protected string $moduleCode = 'PR';
    protected string $formatPattern = 'PR.{DEPT}/{YEAR}/{MONTH}/{SEQUENCE}';
    
    public function __construct(NumberingService $numberingService)
    {
        $this->numberingService = $numberingService;
    }
    
    /**
     * Generate PR number for WNS business unit
     * Uses cross-department sequential numbering (yearly reset only)
     */
    public function generatePRNumber(User $user, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        
        // Get user's primary business unit (WNS)
        $businessUnit = $this->getWNSBusinessUnit();
        if (!$businessUnit) {
            throw new \Exception('WNS business unit not found');
        }
        
        // Get user's department
        $department = $user->primaryDepartment;
        if (!$department || $department->business_unit_id !== $businessUnit->id) {
            throw new \Exception('User must belong to WNS business unit');
        }
        
        // Ensure PR numbering module exists
        $this->ensurePRModule($businessUnit);
        
        // For WNS, use cross-department sequential numbering
        // Pass department_id as null to use shared sequence across all departments
        // But pass department info for formatting purposes
        $result = $this->numberingService->generateNumber(
            $businessUnit->id,
            $this->moduleCode,
            null, // null department_id for cross-department shared sequence
            $date->year,
            null  // null month for yearly reset only
        );
        
        // Override formatted number with WNS-specific format
        $result['formatted_number'] = $this->formatWNSPRNumber(
            $department->code,
            $date->year,
            $date->month,
            $result['sequence_number']
        );
        
        $result['department_code'] = $department->code;
        $result['month'] = $date->month;
        
        return $result;
    }
    
    /**
     * Create PR with auto-generated number
     */
    public function createPRWithNumber(User $user, array $prData): PurchaseRequest
    {
        return DB::transaction(function () use ($user, $prData) {
            // Generate PR number
            $numberResult = $this->generatePRNumber($user, isset($prData['date_of_request']) ? Carbon::parse($prData['date_of_request']) : null);
            
            // Prepare PR data
            $prCreateData = array_merge($prData, [
                'pr_number' => $numberResult['formatted_number'],
                'business_unit_id' => $numberResult['business_unit_code'] === 'WNS' ? $this->getWNSBusinessUnit()->id : null,
                'department_id' => $user->primary_department_id,
                'user_id' => $user->id,
                'sequence_id' => $numberResult['sequence_id'],
                'date_of_request' => $prData['date_of_request'] ?? now()->toDateString(),
                'status' => 'draft',
                'currency' => $prData['currency'] ?? 'IDR',
                'keperluan' => $prData['keperluan'] ?? null, // Include keperluan field
            ]);
            
            // Create the PR
            $pr = PurchaseRequest::create($prCreateData);
            
            // Add to edit history
            $pr->addToEditHistory('created', 'PR created with number: ' . $numberResult['formatted_number'], $user->id);
            
            return $pr;
        });
    }
    
    /**
     * Void PR and trigger resequencing
     */
    public function voidPR(PurchaseRequest $pr, User $user, string $reason): bool
    {
        return DB::transaction(function () use ($pr, $user, $reason) {
            // Void the PR
            $success = $pr->void($user, $reason);
            
            if ($success) {
                // Trigger resequencing through numbering service
                $this->numberingService->voidNumber($pr->pr_number, $pr->sequence_id);
                
                // Log the void action
                activity()
                    ->performedOn($pr)
                    ->causedBy($user)
                    ->withProperties([
                        'pr_number' => $pr->pr_number,
                        'reason' => $reason,
                        'voided_at' => now()->toISOString(),
                    ])
                    ->log('PR voided');
            }
            
            return $success;
        });
    }
    
    /**
     * Get next available PR number preview for WNS cross-department numbering
     */
    public function getNextPRNumberPreview(User $user, ?Carbon $date = null): array
    {
        $date = $date ?? now();
        
        $businessUnit = $this->getWNSBusinessUnit();
        $department = $user->primaryDepartment;
        
        if (!$businessUnit || !$department) {
            throw new \Exception('Invalid user business unit or department');
        }
        
        // Get sequence status using cross-department logic (null department_id)
        $status = $this->numberingService->getSequenceStatus(
            $businessUnit->id,
            $this->moduleCode,
            null, // null for cross-department shared sequence
            $date->year,
            null  // null for yearly reset only
        );
        
        // Handle null status by providing default values
        if (!$status) {
            $status = [
                'current_number' => 0,
                'max_number' => 999,
                'available_numbers' => 999,
                'void_numbers' => [],
                'next_number' => 1,
            ];
        }
        
        // Format preview number with current user's department
        $previewNumber = $this->formatWNSPRNumber(
            $department->code,
            $date->year,
            $date->month,
            $status['next_number']
        );
        
        return [
            'preview_number' => $previewNumber,
            'next_sequence' => $status['next_number'],
            'year' => $date->year,
            'month' => $date->month,
            'department_code' => $department->code,
            'available_numbers' => $status['available_numbers'],
            'note' => 'Cross-department sequential numbering - shared across all WNS departments',
        ];
    }
    
    /**
     * Format PR number according to WNS standard with cross-department sequencing
     * Format: PR.DEPT/YEAR/MONTH/SEQUENCE (where SEQUENCE is shared across departments)
     */
    protected function formatWNSPRNumber(string $deptCode, int $year, int $month, int $sequence): string
    {
        return sprintf(
            'PR.%s/%d/%02d/%03d',
            $deptCode,
            $year,
            $month,
            $sequence
        );
    }
    
    /**
     * Format PR number according to WNS standard (legacy method for backward compatibility)
     */
    protected function formatPRNumber(string $deptCode, int $year, int $month, int $sequence): string
    {
        return $this->formatWNSPRNumber($deptCode, $year, $month, $sequence);
    }
    
    /**
     * Parse PR number to extract components
     */
    public function parsePRNumber(string $prNumber): array
    {
        // Expected format: PR.DEPT/YEAR/MONTH/SEQUENCE
        $pattern = '/^PR\.([A-Z]+)\/(\d{4})\/(\d{2})\/(\d{3})$/';
        
        if (preg_match($pattern, $prNumber, $matches)) {
            return [
                'department_code' => $matches[1],
                'year' => (int) $matches[2],
                'month' => (int) $matches[3],
                'sequence' => (int) $matches[4],
                'valid' => true,
            ];
        }
        
        return ['valid' => false];
    }
    
    /**
     * Validate PR number format
     */
    public function validatePRNumber(string $prNumber): bool
    {
        $parsed = $this->parsePRNumber($prNumber);
        return $parsed['valid'] ?? false;
    }
    
    /**
     * Get WNS business unit
     */
    protected function getWNSBusinessUnit(): ?BusinessUnit
    {
        return BusinessUnit::where('code', 'WNS')->where('is_active', true)->first();
    }
    
    /**
     * Ensure PR numbering module exists for WNS with cross-department configuration
     */
    protected function ensurePRModule(BusinessUnit $businessUnit): NumberingModule
    {
        return NumberingModule::firstOrCreate(
            [
                'business_unit_id' => $businessUnit->id,
                'module_code' => $this->moduleCode,
            ],
            [
                'module_name' => 'Purchase Request',
                'format_pattern' => $this->formatPattern,
                'config' => [
                    'sequence_padding' => 3,
                    'max_number' => 999,
                    'reset_annually' => true,  // Reset yearly only
                    'reset_monthly' => false,  // No monthly reset
                    'cross_department' => true, // Cross-department sequential numbering
                    'shared_sequence' => true,  // Use shared sequence across departments
                ],
                'is_active' => true,
            ]
        );
    }
    
    /**
     * Get PR numbering statistics for WNS
     */
    public function getPRStatistics(?int $year = null, ?int $month = null): array
    {
        $businessUnit = $this->getWNSBusinessUnit();
        if (!$businessUnit) {
            return [];
        }
        
        $baseStats = $this->numberingService->getNumberingStatistics($businessUnit->id, $this->moduleCode);
        
        // Add PR-specific statistics
        $query = PurchaseRequest::where('business_unit_id', $businessUnit->id);
        
        if ($year) {
            $query->whereYear('date_of_request', $year);
        }
        
        if ($month) {
            $query->whereMonth('date_of_request', $month);
        }
        
        $prStats = [
            'total_prs' => $query->count(),
            'draft_prs' => $query->clone()->where('status', 'draft')->count(),
            'submitted_prs' => $query->clone()->where('status', 'submitted')->count(),
            'approved_prs' => $query->clone()->where('status', 'approved')->count(),
            'rejected_prs' => $query->clone()->where('status', 'rejected')->count(),
            'voided_prs' => $query->clone()->where('status', 'voided')->count(),
            'in_approval_prs' => $query->clone()->where('status', 'in_approval')->count(),
        ];
        
        // PR by department
        $prByDepartment = $query->clone()
            ->join('departments', 'purchase_requests.department_id', '=', 'departments.id')
            ->groupBy('departments.code', 'departments.name')
            ->selectRaw('departments.code, departments.name, COUNT(*) as count')
            ->pluck('count', 'code')
            ->toArray();
        
        return array_merge($baseStats, [
            'pr_statistics' => $prStats,
            'pr_by_department' => $prByDepartment,
            'period' => [
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }
    
    /**
     * Get monthly PR generation trend
     */
    public function getMonthlyTrend(int $year): array
    {
        $businessUnit = $this->getWNSBusinessUnit();
        if (!$businessUnit) {
            return [];
        }
        
        $monthlyData = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $count = PurchaseRequest::where('business_unit_id', $businessUnit->id)
                ->whereYear('date_of_request', $year)
                ->whereMonth('date_of_request', $month)
                ->count();
                
            $monthlyData[] = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('M'),
                'count' => $count,
            ];
        }
        
        return $monthlyData;
    }
    
    /**
     * Validate user can create PR in WNS
     */
    public function validateUserCanCreatePR(User $user): bool
    {
        // User must have primary department in WNS business unit
        if (!$user->primaryDepartment) {
            return false;
        }
        
        $businessUnit = $this->getWNSBusinessUnit();
        if (!$businessUnit) {
            return false;
        }
        
        return $user->primaryDepartment->business_unit_id === $businessUnit->id;
    }
    
    /**
     * Get available departments for PR creation in WNS
     */
    public function getAvailableDepartments(): array
    {
        $businessUnit = $this->getWNSBusinessUnit();
        if (!$businessUnit) {
            return [];
        }
        
        return $businessUnit->activeDepartments()
            ->orderBy('name')
            ->get()
            ->map(function ($dept) {
                return [
                    'id' => $dept->id,
                    'code' => $dept->code,
                    'name' => $dept->name,
                    'full_name' => $dept->getFullNameAttribute(),
                ];
            })
            ->toArray();
    }
    
    /**
     * Get PR number history for a user
     */
    public function getUserPRHistory(User $user, int $limit = 10): array
    {
        return PurchaseRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($pr) {
                return [
                    'id' => $pr->id,
                    'pr_number' => $pr->pr_number,
                    'status' => $pr->status,
                    'used_for' => $pr->used_for,
                    'total_amount' => $pr->total_amount,
                    'currency' => $pr->currency,
                    'date_of_request' => $pr->date_of_request->format('Y-m-d'),
                    'created_at' => $pr->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }
}