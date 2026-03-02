<?php

namespace Database\Seeders;

use App\Models\Core\User;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivitySamplingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates 3-6 activities per user per day for the last 30 days.
     */
    public function run(): void
    {
        // Activity types are seeded per-department via WNS/MRP seeders
        // Do NOT call global ActivityTypeSeeder - it creates duplicates

        // Get all active users with departments
        $users = User::whereNotNull('primary_department_id')
            ->where('is_active', true)
            ->with('primaryDepartment')
            ->get();

        if ($users->isEmpty()) {
            $this->command->warn('No users found with departments. Skipping activity seeding.');

            return;
        }

        // Get activity types with sub-activities
        $activityTypes = ActivityType::with('subActivities')->where('is_active', true)->get();

        if ($activityTypes->isEmpty()) {
            $this->command->warn('No activity types found. Run ActivityTypeSeeder first.');

            return;
        }

        $taskTemplates = $this->getTaskTemplates();
        $statuses = ['planned', 'in_progress', 'completed'];
        $priorities = ['low', 'medium', 'high'];

        $totalTasks = 0;
        $startDate = now()->subDays(30);
        $endDate = now();

        $this->command->info('Creating activity samples for '.$users->count().' users...');

        DB::beginTransaction();
        try {
            foreach ($users as $user) {
                $department = $user->primaryDepartment;
                if (! $department) {
                    continue;
                }

                $businessUnitId = $department->business_unit_id;
                $currentDate = $startDate->copy();

                while ($currentDate <= $endDate) {
                    // Skip weekends (optional - remove if you want weekend tasks)
                    if ($currentDate->isWeekend()) {
                        $currentDate->addDay();

                        continue;
                    }

                    // Random 3-6 tasks per day
                    $tasksPerDay = rand(3, 6);

                    for ($i = 0; $i < $tasksPerDay; $i++) {
                        $activityType = $activityTypes->random();
                        $subActivity = $activityType->subActivities->isNotEmpty()
                            ? $activityType->subActivities->random()
                            : null;

                        // Get random task template based on activity type
                        $template = $this->getRandomTemplate($taskTemplates, $activityType->code);

                        // Determine status based on date
                        $daysDiff = $currentDate->diffInDays(now());
                        if ($daysDiff > 7) {
                            // Older tasks are mostly completed
                            $status = rand(1, 10) <= 8 ? 'completed' : 'cancelled';
                        } elseif ($daysDiff > 2) {
                            // Recent tasks mix of completed and in_progress
                            $status = $statuses[array_rand(['completed', 'completed', 'in_progress'])];
                        } else {
                            // Today/yesterday tasks are planned or in_progress
                            $status = $statuses[array_rand(['planned', 'in_progress', 'in_progress'])];
                        }

                        $taskDate = $currentDate->copy();
                        $dueDate = $taskDate->copy()->addDays(rand(1, 5));

                        $taskData = [
                            'business_unit_id' => $businessUnitId,
                            'department_id' => $department->id,
                            'created_by' => $user->id,
                            'activity_type_id' => $activityType->id,
                            'sub_activity_id' => $subActivity?->id,
                            'task_title' => $template['title'],
                            'task_date' => $taskDate->format('Y-m-d'),
                            'due_date' => $dueDate->format('Y-m-d'),
                            'notes' => $template['notes'],
                            'status' => $status,
                            'priority' => $priorities[array_rand($priorities)],
                            'created_at' => $taskDate->copy()->setTime(rand(7, 9), rand(0, 59)),
                            'updated_at' => $taskDate->copy()->setTime(rand(16, 18), rand(0, 59)),
                        ];

                        // Add timestamps based on status
                        if (in_array($status, ['in_progress', 'completed'])) {
                            $taskData['started_at'] = $taskDate->copy()->setTime(rand(8, 10), rand(0, 59));
                        }

                        if ($status === 'completed') {
                            $completedAt = $taskDate->copy()->setTime(rand(14, 17), rand(0, 59));
                            $taskData['completed_at'] = $completedAt;
                            $taskData['completed_by'] = $user->id;
                            $taskData['duration_minutes'] = rand(30, 240);
                        }

                        if ($status === 'cancelled') {
                            $taskData['cancellation_reason'] = 'Dibatalkan karena perubahan prioritas';
                        }

                        $task = EmployeeTask::create($taskData);

                        // Add creator as owner participant
                        DB::table('task_participants')->insert([
                            'employee_task_id' => $task->id,
                            'user_id' => $user->id,
                            'is_owner' => true,
                            'joined_at' => $taskData['created_at'],
                            'created_at' => $taskData['created_at'],
                            'updated_at' => $taskData['updated_at'],
                        ]);

                        // Randomly add 1-2 other participants from same department (30% chance)
                        if (rand(1, 10) <= 3) {
                            $colleagues = User::where('primary_department_id', $department->id)
                                ->where('id', '!=', $user->id)
                                ->where('is_active', true)
                                ->inRandomOrder()
                                ->take(rand(1, 2))
                                ->get();

                            foreach ($colleagues as $colleague) {
                                DB::table('task_participants')->insert([
                                    'employee_task_id' => $task->id,
                                    'user_id' => $colleague->id,
                                    'is_owner' => false,
                                    'joined_at' => $taskData['created_at'],
                                    'created_at' => $taskData['created_at'],
                                    'updated_at' => $taskData['updated_at'],
                                ]);
                            }
                        }

                        $totalTasks++;
                    }

                    $currentDate->addDay();
                }
            }

            DB::commit();

            $this->command->info("Successfully created {$totalTasks} activity tasks!");
            $this->command->info("Date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
            $this->command->info("Users: {$users->count()}");
            $this->command->info('Average tasks per user: '.round($totalTasks / $users->count()));

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Failed to seed activities: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get task templates organized by activity type
     */
    protected function getTaskTemplates(): array
    {
        return [
            'MEETING' => [
                ['title' => 'Meeting koordinasi tim', 'notes' => 'Koordinasi progress dan planning'],
                ['title' => 'Meeting dengan vendor', 'notes' => 'Diskusi penawaran dan negosiasi'],
                ['title' => 'Meeting review project', 'notes' => 'Review milestone dan deliverables'],
                ['title' => 'Meeting client presentation', 'notes' => 'Presentasi progress ke client'],
                ['title' => 'Meeting budget planning', 'notes' => 'Perencanaan anggaran'],
                ['title' => 'Meeting evaluasi kinerja', 'notes' => 'Evaluasi kinerja tim'],
            ],
            'WEBDEV' => [
                ['title' => 'Fix bug pada modul login', 'notes' => 'Perbaikan issue authentication'],
                ['title' => 'Develop fitur dashboard', 'notes' => 'Implementasi dashboard analytics'],
                ['title' => 'Update UI komponen', 'notes' => 'Perbaikan tampilan sesuai design'],
                ['title' => 'Code review PR', 'notes' => 'Review pull request dari tim'],
                ['title' => 'Deployment ke staging', 'notes' => 'Deploy versi terbaru ke staging'],
                ['title' => 'Optimasi query database', 'notes' => 'Perbaikan performa query'],
            ],
            'EVENT' => [
                ['title' => 'Persiapan event gathering', 'notes' => 'Koordinasi venue dan catering'],
                ['title' => 'Setup technical event', 'notes' => 'Persiapan teknis acara'],
                ['title' => 'Follow-up post event', 'notes' => 'Evaluasi dan dokumentasi event'],
                ['title' => 'Koordinasi sponsorship', 'notes' => 'Komunikasi dengan sponsor'],
            ],
            'INTERNAL' => [
                ['title' => 'Daily standup meeting', 'notes' => 'Update progress harian'],
                ['title' => 'Weekly team review', 'notes' => 'Review mingguan tim'],
                ['title' => 'Monthly report preparation', 'notes' => 'Persiapan laporan bulanan'],
                ['title' => 'Sprint planning', 'notes' => 'Planning sprint berikutnya'],
            ],
            'ADMIN' => [
                ['title' => 'Update dokumentasi SOP', 'notes' => 'Revisi SOP sesuai proses terbaru'],
                ['title' => 'Pengelolaan email', 'notes' => 'Respon email dan follow-up'],
                ['title' => 'Pembuatan laporan', 'notes' => 'Compile data untuk laporan'],
                ['title' => 'Filing dokumen', 'notes' => 'Pengarsipan dokumen'],
                ['title' => 'Input data sistem', 'notes' => 'Entry data ke sistem'],
            ],
            'TRAINING' => [
                ['title' => 'Training internal sistem', 'notes' => 'Pelatihan penggunaan sistem'],
                ['title' => 'Workshop skill development', 'notes' => 'Pengembangan skill tim'],
                ['title' => 'Self learning online course', 'notes' => 'Belajar mandiri via online'],
                ['title' => 'Mentoring junior', 'notes' => 'Bimbingan anggota tim baru'],
            ],
        ];
    }

    /**
     * Get random template based on activity type code
     */
    protected function getRandomTemplate(array $templates, string $activityCode): array
    {
        if (isset($templates[$activityCode])) {
            return $templates[$activityCode][array_rand($templates[$activityCode])];
        }

        // Fallback to random from all templates
        $allTemplates = array_merge(...array_values($templates));

        return $allTemplates[array_rand($allTemplates)];
    }
}
