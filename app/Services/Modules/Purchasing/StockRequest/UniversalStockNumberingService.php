<?php

namespace App\Services\Modules\Purchasing\StockRequest;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\NumberingModule;
use App\Models\Core\User;
use App\Services\Core\NumberingService;
use Carbon\Carbon;

class UniversalStockNumberingService
{
    protected NumberingService $numberingService;

    protected string $moduleCode = 'ST';

    protected string $formatPattern = 'ST/{BU_CODE}/{YYYYMM}/{SEQUENCE}';

    public function __construct(NumberingService $numberingService)
    {
        $this->numberingService = $numberingService;
    }

    /**
     * Generate Stock Request number for any business unit
     * Format: ST/{BU_CODE}/{YYYYMM}/{XXX}
     *
     * @param  int|null  $businessUnitId  - If null, uses session current_business_unit_id
     * @param  int|null  $departmentId  - If null, uses user's primary department in the BU
     */
    public function generateStockNumber(
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

        // Ensure ST numbering module exists for this business unit
        $this->ensureStockModule($businessUnit);

        // Generate sequence number
        // Use business unit only for continuous sequence per BU across all departments
        $result = $this->numberingService->generateNumber(
            $businessUnit->id,
            $this->moduleCode,
            null, // No department separation - continuous per BU
            $date->year,
            null  // No monthly reset - yearly reset only
        );

        // Format the stock request number with new simplified format
        $result['formatted_number'] = $this->formatUniversalStockNumber(
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
     * Format Stock Request number with simplified format
     * Format: ST/{BU_CODE}/{YYYYMM}/{XXX}
     */
    protected function formatUniversalStockNumber(
        string $buCode,
        int $year,
        int $month,
        int $sequence
    ): string {
        return sprintf(
            'ST/%s/%d%02d/%03d',
            $buCode,
            $year,
            $month,
            $sequence
        );
    }

    /**
     * Ensure stock numbering module exists for business unit
     */
    protected function ensureStockModule(BusinessUnit $businessUnit): void
    {
        $module = NumberingModule::active()
            ->forBusinessUnit($businessUnit->id)
            ->byCode($this->moduleCode)
            ->first();

        if (! $module) {
            NumberingModule::create([
                'business_unit_id' => $businessUnit->id,
                'module_code' => $this->moduleCode,
                'module_name' => 'Stock Request',
                'format_pattern' => $this->formatPattern,
                'config' => [
                    'prefix' => 'ST',
                    'separator' => '/',
                    'sequence_length' => 3,
                    'reset_period' => 'yearly',
                ],
                'is_active' => true,
            ]);
        }
    }

    /**
     * Validate stock request number format
     */
    public function validateStockNumber(string $stNumber): bool
    {
        // Format: ST/{BU}/{YYYYMM}/{XXX}
        // Example: ST/WNS/202412/001
        $pattern = '/^ST\/[A-Z]{2,5}\/\d{6}\/\d{3}$/';

        return (bool) preg_match($pattern, $stNumber);
    }

    /**
     * Parse stock request number into components
     */
    public function parseStockNumber(string $stNumber): array
    {
        if (! $this->validateStockNumber($stNumber)) {
            return [
                'valid' => false,
                'business_unit_code' => null,
                'year' => null,
                'month' => null,
                'sequence' => null,
            ];
        }

        // Extract components
        preg_match('/^ST\/([A-Z]{2,5})\/(\d{4})(\d{2})\/(\d{3})$/', $stNumber, $matches);

        return [
            'valid' => true,
            'business_unit_code' => $matches[1] ?? null,
            'year' => (int) ($matches[2] ?? 0),
            'month' => (int) ($matches[3] ?? 0),
            'sequence' => (int) ($matches[4] ?? 0),
        ];
    }
}
