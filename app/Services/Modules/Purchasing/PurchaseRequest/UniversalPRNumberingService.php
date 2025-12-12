<?php

namespace App\Services\Modules\Purchasing\PurchaseRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\User;
use App\Services\Core\NumberingService;
use Carbon\Carbon;

class UniversalPRNumberingService
{
    protected NumberingService $numberingService;

    protected string $moduleCode = 'PR';

    protected string $formatPattern = 'PR.{BU_CODE}/{YYYYMM}/{SEQUENCE}';

    public function __construct(NumberingService $numberingService)
    {
        $this->numberingService = $numberingService;
    }

    /**
     * Generate PR number for any business unit
     * Format: PR.{BU_CODE}-{DEPT_CODE}/{YYYYMM}/{XX}
     *
     * @param  int|null  $businessUnitId  - If null, uses session current_business_unit_id
     * @param  int|null  $departmentId  - If null, uses user's primary department in the BU
     */
    public function generatePRNumber(
        User $user,
        ?int $businessUnitId = null,
        ?int $departmentId = null,
        ?Carbon $date = null
    ): array {
        $date = $date ?? now();

        // Determine business unit
        $businessUnit = $this->resolveBusinessUnit($user, $businessUnitId);
        if (! $businessUnit) {
            throw new \Exception('Business unit not found or user has no access');
        }

        // Determine department
        $department = $this->resolveDepartment($user, $businessUnit, $departmentId);
        if (! $department) {
            throw new \Exception('Department not found or user has no access');
        }

        // Ensure PR numbering module exists for this business unit
        $this->ensurePRModule($businessUnit);

        // Generate sequence number
        // Use business unit only for continuous sequence per BU across all departments

        $result = $this->numberingService->generateNumber(
            $businessUnit->id,
            $this->moduleCode,
            null, // No department separation - continuous per BU
            $date->year,
            null  // No monthly reset - yearly reset only
        );

        // Format the PR number with new simplified format
        $result['formatted_number'] = $this->formatUniversalPRNumber(
            $businessUnit->code,
            $date->year,
            $date->month,
            $result['sequence_number']
        );

        // Add additional metadata
        $result['business_unit_code'] = $businessUnit->code;
        $result['business_unit_name'] = $businessUnit->name;
        $result['department_code'] = $department->code;
        $result['department_name'] = $department->name;
        $result['year'] = $date->year;
        $result['month'] = $date->month;

        return $result;
    }

    /**
     * Resolve business unit from various sources
     */
    protected function resolveBusinessUnit(User $user, ?int $businessUnitId): ?BusinessUnit
    {
        // 1. Use provided business unit ID
        if ($businessUnitId) {
            $businessUnit = BusinessUnit::find($businessUnitId);
            if ($businessUnit && $this->userHasAccessToBusinessUnit($user, $businessUnit)) {
                return $businessUnit;
            }
        }

        // 2. Use session current business unit
        $sessionBuId = session('current_business_unit_id');
        if ($sessionBuId) {
            $businessUnit = BusinessUnit::find($sessionBuId);
            if ($businessUnit && $this->userHasAccessToBusinessUnit($user, $businessUnit)) {
                return $businessUnit;
            }
        }

        // 3. For super admins, allow any active business unit (fallback to first active)
        // Use deterministic ORDER BY to ensure consistent results
        if ($user->global_role === 'super_admin') {
            return BusinessUnit::where('is_active', true)
                ->orderBy('id', 'asc')
                ->first();
        }

        // 4. Use user's primary business unit
        if ($user->primaryDepartment && $user->primaryDepartment->businessUnit) {
            return $user->primaryDepartment->businessUnit;
        }

        return null;
    }

    /**
     * Resolve department from various sources
     */
    protected function resolveDepartment(User $user, BusinessUnit $businessUnit, ?int $departmentId): ?Department
    {
        // 1. Use provided department ID (must be in the business unit)
        if ($departmentId) {
            $department = Department::where('id', $departmentId)
                ->where('business_unit_id', $businessUnit->id)
                ->where('is_active', true)
                ->first();
            if ($department) {
                return $department;
            }
        }

        // 2. Use user's primary department if it's in this business unit
        if ($user->primaryDepartment &&
            $user->primaryDepartment->business_unit_id === $businessUnit->id) {
            return $user->primaryDepartment;
        }

        // 3. For super admins, use any department in the business unit
        // Use deterministic ORDER BY to ensure consistent results
        if ($user->global_role === 'super_admin') {
            return $businessUnit->activeDepartments()
                ->orderBy('id', 'asc')
                ->first();
        }

        // 4. Find user's department assignment in this business unit
        $userBuAssignment = $user->businessUnits()
            ->where('business_unit_id', $businessUnit->id)
            ->where('is_active', true)
            ->first();

        if ($userBuAssignment && $userBuAssignment->department_id) {
            return Department::find($userBuAssignment->department_id);
        }

        return null;
    }

    /**
     * Check if user has access to business unit
     */
    protected function userHasAccessToBusinessUnit(User $user, BusinessUnit $businessUnit): bool
    {
        // Super admins have access to all business units
        if ($user->global_role === 'super_admin') {
            return true;
        }

        // Check if user has assignment to this business unit
        return $user->businessUnits()
            ->where('business_unit_id', $businessUnit->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Format PR number with simplified format
     * Format: PR.{BU_CODE}/{YYYYMM}/{XXX}
     */
    protected function formatUniversalPRNumber(
        string $buCode,
        int $year,
        int $month,
        int $sequence
    ): string {
        return sprintf(
            'PR.%s/%d%02d/%03d',
            $buCode,
            $year,
            $month,
            $sequence
        );
    }

    /**
     * Parse universal PR number to extract components
     */
    public function parseUniversalPRNumber(string $prNumber): array
    {
        // Expected format: PR.BU/YYYYMM/XXX
        $pattern = '/^PR\.([A-Z]+)\/(\d{6})\/(\d{3})$/';

        if (preg_match($pattern, $prNumber, $matches)) {
            $yearMonth = $matches[2];
            $year = (int) substr($yearMonth, 0, 4);
            $month = (int) substr($yearMonth, 4, 2);

            return [
                'business_unit_code' => $matches[1],
                'year' => $year,
                'month' => $month,
                'sequence' => (int) $matches[3],
                'year_month' => $yearMonth,
                'valid' => true,
            ];
        }

        return ['valid' => false];
    }

    /**
     * Validate universal PR number format
     */
    public function validatePRNumber(string $prNumber): bool
    {
        $parsed = $this->parseUniversalPRNumber($prNumber);

        return $parsed['valid'] ?? false;
    }

    /**
     * Get next PR number preview
     */
    public function getNextPRNumberPreview(
        User $user,
        ?int $businessUnitId = null,
        ?int $departmentId = null,
        ?Carbon $date = null
    ): array {
        $date = $date ?? now();

        $businessUnit = $this->resolveBusinessUnit($user, $businessUnitId);
        $department = $this->resolveDepartment($user, $businessUnit, $departmentId);

        if (! $businessUnit || ! $department) {
            throw new \Exception('Cannot resolve business unit or department for preview');
        }

        // Get sequence status
        $status = $this->numberingService->getSequenceStatus(
            $businessUnit->id,
            $this->moduleCode,
            null, // No department separation
            $date->year,
            null  // No monthly separation
        );

        // Handle null status
        if (! $status) {
            $status = [
                'current_number' => 0,
                'next_number' => 1,
                'available_numbers' => 999,
            ];
        }

        // Format preview number
        $previewNumber = $this->formatUniversalPRNumber(
            $businessUnit->code,
            $date->year,
            $date->month,
            $status['next_number']
        );

        return [
            'preview_number' => $previewNumber,
            'next_sequence' => $status['next_number'],
            'business_unit' => [
                'id' => $businessUnit->id,
                'code' => $businessUnit->code,
                'name' => $businessUnit->name,
            ],
            'department' => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
            ],
            'year' => $date->year,
            'month' => $date->month,
            'available_numbers' => $status['available_numbers'] ?? 99,
        ];
    }

    /**
     * Get user's available business units for PR creation
     */
    public function getUserAvailableBusinessUnits(User $user): array
    {
        if ($user->global_role === 'super_admin') {
            // Super admins can create PR for any business unit
            return BusinessUnit::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function ($bu) {
                    return [
                        'id' => $bu->id,
                        'code' => $bu->code,
                        'name' => $bu->name,
                        'departments' => $bu->activeDepartments()
                            ->orderBy('name')
                            ->get()
                            ->map(function ($dept) {
                                return [
                                    'id' => $dept->id,
                                    'code' => $dept->code,
                                    'name' => $dept->name,
                                ];
                            })
                            ->toArray(),
                    ];
                })
                ->toArray();
        }

        // Regular users - only their assigned business units
        return $user->businessUnits()
            ->with(['businessUnit.activeDepartments'])
            ->where('is_active', true)
            ->get()
            ->map(function ($assignment) {
                $bu = $assignment->businessUnit;

                return [
                    'id' => $bu->id,
                    'code' => $bu->code,
                    'name' => $bu->name,
                    'user_role' => $assignment->role,
                    'departments' => $bu->activeDepartments()
                        ->orderBy('name')
                        ->get()
                        ->map(function ($dept) {
                            return [
                                'id' => $dept->id,
                                'code' => $dept->code,
                                'name' => $dept->name,
                            ];
                        })
                        ->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * Ensure PR numbering module exists for business unit
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
                    'reset_annually' => true,   // Yearly reset
                    'reset_monthly' => false,   // No monthly reset
                    'cross_department' => true, // Continuous across departments
                    'shared_sequence' => true,  // Shared sequence for all departments in BU
                ],
                'is_active' => true,
            ]
        );
    }

    /**
     * Get PR statistics for business unit
     */
    public function getPRStatistics(int $businessUnitId, ?int $year = null, ?int $month = null): array
    {
        $businessUnit = BusinessUnit::find($businessUnitId);
        if (! $businessUnit) {
            return [];
        }

        $baseStats = $this->numberingService->getNumberingStatistics($businessUnit->id, $this->moduleCode);

        // Add PR-specific statistics if PR model exists
        // This would need to be implemented based on your PR model structure

        return array_merge($baseStats, [
            'business_unit' => [
                'id' => $businessUnit->id,
                'code' => $businessUnit->code,
                'name' => $businessUnit->name,
            ],
            'period' => [
                'year' => $year,
                'month' => $month,
            ],
        ]);
    }
}
