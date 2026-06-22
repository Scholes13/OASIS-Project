<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Color mapping from name to hex (Tailwind-inspired palette)
     */
    private array $colorMap = [
        'blue' => '#3b82f6',      // Blue-500
        'indigo' => '#6366f1',    // Indigo-500
        'purple' => '#a855f7',    // Purple-500
        'gray' => '#6b7280',      // Gray-500
        'yellow' => '#f59e0b',    // Amber-500 (more visible than pure yellow)
        'green' => '#22c55e',     // Green-500
        'red' => '#ef4444',       // Red-500
        'pink' => '#ec4899',      // Pink-500
        'orange' => '#f97316',    // Orange-500
        'teal' => '#14b8a6',      // Teal-500
        'cyan' => '#06b6d4',      // Cyan-500
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update each activity type's color from name to hex
        foreach ($this->colorMap as $name => $hex) {
            DB::table('employee_activity_types')
                ->where('color', $name)
                ->update(['color' => $hex]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse mapping (hex to name)
        $reverseMap = array_flip($this->colorMap);

        foreach ($reverseMap as $hex => $name) {
            DB::table('employee_activity_types')
                ->where('color', $hex)
                ->update(['color' => $name]);
        }
    }
};
