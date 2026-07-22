<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->boolean('routes_directly_to_purchasing')
                ->default(false)
                ->after('status');
            $table->boolean('skips_ga_review')
                ->default(false)
                ->after('routes_directly_to_purchasing');
        });

        DB::table('stock_requests')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('departments')
                    ->whereColumn('departments.id', 'stock_requests.department_id')
                    ->whereColumn('departments.business_unit_id', 'stock_requests.business_unit_id')
                    ->where('departments.is_ga_stock_review_department', true);
            })
            ->update(['skips_ga_review' => true]);

        DB::table('stock_requests')
            ->where('skips_ga_review', true)
            ->where(function ($query) {
                $query->whereNotExists(function ($approvalQuery) {
                    $approvalQuery->selectRaw('1')
                        ->from('stock_approvals')
                        ->whereColumn('stock_approvals.stock_request_id', 'stock_requests.id');
                })->orWhereIn('status', ['submitted', 'in_approval', 'ga_review']);
            })
            ->update(['routes_directly_to_purchasing' => true]);
    }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropColumn(['routes_directly_to_purchasing', 'skips_ga_review']);
        });
    }
};
