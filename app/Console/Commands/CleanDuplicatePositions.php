<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Position;

class CleanDuplicatePositions extends Command
{
    protected $signature = 'positions:clean-duplicates';
    protected $description = 'Clean duplicate positions based on department_id and code';

    public function handle()
    {
        $this->info('Checking for duplicate positions...');

        // Find duplicates
        $duplicates = DB::select('
            SELECT department_id, code, COUNT(*) as count 
            FROM positions 
            GROUP BY department_id, code 
            HAVING COUNT(*) > 1
        ');

        if (empty($duplicates)) {
            $this->info('No duplicate positions found.');
            return;
        }

        $this->info('Found ' . count($duplicates) . ' duplicate position groups:');

        foreach ($duplicates as $duplicate) {
            $this->line("Department ID: {$duplicate->department_id}, Code: {$duplicate->code}, Count: {$duplicate->count}");
            
            // Get all positions with this department_id and code
            $positions = Position::where('department_id', $duplicate->department_id)
                ->where('code', $duplicate->code)
                ->orderBy('id')
                ->get();

            // Keep the first one, delete the rest
            $kept = $positions->first();
            $toDelete = $positions->skip(1);

            foreach ($toDelete as $position) {
                $this->line("  Deleting duplicate: ID {$position->id} - {$position->name}");
                $position->delete();
            }

            $this->line("  Kept: ID {$kept->id} - {$kept->name}");
        }

        $this->info('Duplicate positions cleaned successfully!');
    }
}