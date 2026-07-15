<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $strategicSourcingDepartments = DB::table('departments')
            ->where('code', 'SS')
            ->where('is_active', true)
            ->get(['id', 'business_unit_id']);

        foreach ($strategicSourcingDepartments as $department) {
            DB::table('departments')
                ->where('id', $department->id)
                ->update(['is_purchasing_department' => true]);
        }
    }

    public function down(): void {}
};
