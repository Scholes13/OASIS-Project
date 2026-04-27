<?php

namespace Database\Seeders;

use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeTaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates sample tasks for BAS department users with various statuses.
     * Tasks are spread across the current month for calendar demo.
     */
    public function run(): void
    {
        // BAS Department users
        $users = [
            ['id' => 4, 'name' => 'Pramuji Arif Yulianto'],
            ['id' => 5, 'name' => 'Yulia Mekar Rini'],
            ['id' => 6, 'name' => 'Hanung Sastriya'],
        ];

        // Business Unit: WNS (id: 2)
        $businessUnitId = 2;
        // Department: BAS (id: 4)
        $departmentId = 4;

        $tasks = [
            // ========== OVERDUE TASKS ==========
            [
                'created_by' => 4,
                'activity_type_id' => 5, // Administrative
                'sub_activity_id' => 17, // Documentation
                'task_title' => 'Update SOP Pengadaan Barang',
                'due_date' => now()->subDays(5)->format('Y-m-d'),
                'notes' => 'Perlu update SOP pengadaan sesuai regulasi terbaru',
                'status' => 'in_progress',
                'started_at' => now()->subDays(7),
                'participants' => [4, 5],
            ],
            [
                'created_by' => 5,
                'activity_type_id' => 1, // Meeting
                'sub_activity_id' => 4, // Meeting Internal
                'task_title' => 'Koordinasi Budget Q1 2025',
                'due_date' => now()->subDays(3)->format('Y-m-d'),
                'notes' => 'Meeting koordinasi budget dengan semua departemen',
                'status' => 'planned',
                'participants' => [5, 6],
            ],
            [
                'created_by' => 6,
                'activity_type_id' => 5, // Administrative
                'sub_activity_id' => 19, // Report Writing
                'task_title' => 'Laporan Realisasi Anggaran Desember',
                'due_date' => now()->subDays(2)->format('Y-m-d'),
                'notes' => 'Deadline laporan sudah lewat, perlu segera diselesaikan',
                'status' => 'in_progress',
                'started_at' => now()->subDays(4),
                'participants' => [6],
            ],

            // ========== IN PROGRESS TASKS ==========
            [
                'created_by' => 4,
                'activity_type_id' => 2, // Web Development
                'sub_activity_id' => 8, // New Feature
                'task_title' => 'Implementasi Modul Activity Tracking',
                'due_date' => now()->addDays(5)->format('Y-m-d'),
                'notes' => 'Develop fitur tracking aktivitas karyawan',
                'status' => 'in_progress',
                'started_at' => now()->subDays(3),
                'participants' => [4, 6],
            ],
            [
                'created_by' => 5,
                'activity_type_id' => 5, // Administrative
                'sub_activity_id' => 18, // Email
                'task_title' => 'Follow-up Vendor Alat Tulis Kantor',
                'due_date' => now()->addDays(2)->format('Y-m-d'),
                'notes' => 'Konfirmasi harga dan ketersediaan stok',
                'status' => 'in_progress',
                'started_at' => now()->subDays(1),
                'participants' => [5],
            ],
            [
                'created_by' => 4,
                'activity_type_id' => 2, // Web Development
                'sub_activity_id' => 6, // Fix Bug
                'task_title' => 'Perbaikan Bug Export PDF',
                'due_date' => now()->addDays(1)->format('Y-m-d'),
                'notes' => 'Fix issue export PDF yang tidak bisa download',
                'status' => 'in_progress',
                'started_at' => now(),
                'participants' => [4],
            ],

            // ========== PLANNED TASKS (spread across calendar) ==========
            [
                'created_by' => 4,
                'activity_type_id' => 1, // Meeting
                'sub_activity_id' => 5, // Meeting Vendor
                'task_title' => 'Meeting dengan Vendor IT',
                'due_date' => now()->addDays(7)->format('Y-m-d'),
                'notes' => 'Diskusi perpanjangan kontrak maintenance',
                'status' => 'planned',
                'participants' => [4, 5, 6],
            ],
            [
                'created_by' => 6,
                'activity_type_id' => 6, // Training
                'sub_activity_id' => 20, // Internal Training
                'task_title' => 'Training Penggunaan Sistem Oasis',
                'due_date' => now()->addDays(10)->format('Y-m-d'),
                'notes' => 'Training untuk user baru departemen Finance',
                'status' => 'planned',
                'participants' => [6, 4],
            ],
            [
                'created_by' => 5,
                'activity_type_id' => 3, // Event
                'sub_activity_id' => 11, // Event Planning
                'task_title' => 'Persiapan Gathering Akhir Tahun',
                'due_date' => now()->addDays(14)->format('Y-m-d'),
                'notes' => 'Koordinasi venue, catering, dan rundown acara',
                'status' => 'planned',
                'participants' => [5, 4],
            ],
            [
                'created_by' => 4,
                'activity_type_id' => 1, // Meeting
                'sub_activity_id' => 4, // Meeting Internal
                'task_title' => 'Review Kinerja Tim Q4',
                'due_date' => now()->addDays(3)->format('Y-m-d'),
                'notes' => 'Evaluasi kinerja tim selama Q4 2025',
                'status' => 'planned',
                'participants' => [4, 5, 6],
            ],
            [
                'created_by' => 5,
                'activity_type_id' => 5, // Administrative
                'sub_activity_id' => 17, // Documentation
                'task_title' => 'Update Dokumentasi API',
                'due_date' => now()->addDays(8)->format('Y-m-d'),
                'notes' => 'Dokumentasi endpoint API terbaru',
                'status' => 'planned',
                'participants' => [5],
            ],
            [
                'created_by' => 6,
                'activity_type_id' => 2, // Web Development
                'sub_activity_id' => 8, // New Feature
                'task_title' => 'Develop Dashboard Analytics',
                'due_date' => now()->addDays(12)->format('Y-m-d'),
                'notes' => 'Buat dashboard analytics untuk management',
                'status' => 'planned',
                'participants' => [6, 4],
            ],
            [
                'created_by' => 4,
                'activity_type_id' => 1, // Meeting
                'sub_activity_id' => 1, // Meeting Client
                'task_title' => 'Presentasi Progress ke Management',
                'due_date' => now()->addDays(6)->format('Y-m-d'),
                'notes' => 'Presentasi progress development ke BOD',
                'status' => 'planned',
                'participants' => [4],
            ],
            [
                'created_by' => 5,
                'activity_type_id' => 6, // Training
                'sub_activity_id' => 21, // External Training
                'task_title' => 'Workshop Laravel Advanced',
                'due_date' => now()->addDays(18)->format('Y-m-d'),
                'notes' => 'Workshop online Laravel advanced techniques',
                'status' => 'planned',
                'participants' => [5, 4, 6],
            ],
            [
                'created_by' => 6,
                'activity_type_id' => 3, // Event
                'sub_activity_id' => 12, // Event Execution
                'task_title' => 'Company Anniversary Celebration',
                'due_date' => now()->addDays(21)->format('Y-m-d'),
                'notes' => 'Pelaksanaan acara anniversary perusahaan',
                'status' => 'planned',
                'participants' => [6, 5],
            ],

            // ========== COMPLETED TASKS ==========
            [
                'created_by' => 4,
                'activity_type_id' => 4, // Internal Meeting
                'sub_activity_id' => 15, // Weekly Review
                'task_title' => 'Weekly Review Tim BAS',
                'due_date' => now()->subDays(7)->format('Y-m-d'),
                'notes' => 'Review progress mingguan dan planning minggu depan',
                'status' => 'completed',
                'started_at' => now()->subDays(8),
                'completed_at' => now()->subDays(7),
                'completed_by' => 4,
                'duration_minutes' => 90,
                'participants' => [4, 5, 6],
            ],
            [
                'created_by' => 5,
                'activity_type_id' => 2, // Web Development
                'sub_activity_id' => 6, // Fix Bug
                'task_title' => 'Fix Bug Approval Workflow',
                'due_date' => now()->subDays(10)->format('Y-m-d'),
                'notes' => 'Perbaikan bug pada proses approval PR',
                'status' => 'completed',
                'started_at' => now()->subDays(12),
                'completed_at' => now()->subDays(10),
                'completed_by' => 4,
                'duration_minutes' => 240,
                'participants' => [5, 4],
            ],
            [
                'created_by' => 6,
                'activity_type_id' => 5, // Administrative
                'sub_activity_id' => 17, // Documentation
                'task_title' => 'Dokumentasi Proses Stock Request',
                'due_date' => now()->subDays(5)->format('Y-m-d'),
                'notes' => 'Buat dokumentasi lengkap alur stock request',
                'status' => 'completed',
                'started_at' => now()->subDays(8),
                'completed_at' => now()->subDays(6),
                'completed_by' => 6,
                'duration_minutes' => 180,
                'participants' => [6],
            ],
            [
                'created_by' => 4,
                'activity_type_id' => 1, // Meeting
                'sub_activity_id' => 4, // Meeting Internal
                'task_title' => 'Kickoff Meeting Project Oasis v2',
                'due_date' => now()->subDays(14)->format('Y-m-d'),
                'notes' => 'Meeting kickoff untuk development Oasis versi 2',
                'status' => 'completed',
                'started_at' => now()->subDays(14),
                'completed_at' => now()->subDays(14),
                'completed_by' => 4,
                'duration_minutes' => 120,
                'participants' => [4, 5, 6],
            ],
            [
                'created_by' => 5,
                'activity_type_id' => 5, // Administrative
                'sub_activity_id' => 19, // Report Writing
                'task_title' => 'Laporan Progress Development November',
                'due_date' => now()->subDays(20)->format('Y-m-d'),
                'notes' => 'Laporan bulanan progress development',
                'status' => 'completed',
                'started_at' => now()->subDays(22),
                'completed_at' => now()->subDays(20),
                'completed_by' => 5,
                'duration_minutes' => 150,
                'participants' => [5],
            ],

            // ========== CANCELLED TASK ==========
            [
                'created_by' => 5,
                'activity_type_id' => 1, // Meeting
                'sub_activity_id' => 1, // Meeting Client
                'task_title' => 'Meeting dengan Client ABC',
                'due_date' => now()->subDays(3)->format('Y-m-d'),
                'notes' => 'Dibatalkan karena client reschedule',
                'status' => 'cancelled',
                'cancellation_reason' => 'Client meminta reschedule ke bulan depan',
                'participants' => [5, 4],
            ],
        ];

        foreach ($tasks as $taskData) {
            $participants = $taskData['participants'];
            unset($taskData['participants']);

            // Create task
            $task = EmployeeTask::create([
                'business_unit_id' => $businessUnitId,
                'department_id' => $departmentId,
                ...$taskData,
            ]);

            // Add participants
            foreach ($participants as $index => $userId) {
                DB::table('task_participants')->insert([
                    'employee_task_id' => $task->id,
                    'user_id' => $userId,
                    'is_owner' => $index === 0, // First participant is owner
                    'joined_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('Employee tasks seeded successfully! Created '.count($tasks).' tasks.');
        $this->command->info('- 3 overdue tasks');
        $this->command->info('- 3 in progress tasks');
        $this->command->info('- 9 planned tasks (spread across calendar)');
        $this->command->info('- 5 completed tasks');
        $this->command->info('- 1 cancelled task');
    }
}
