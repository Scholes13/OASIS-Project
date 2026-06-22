<?php

namespace Database\Seeders;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Ticket\Ticket;
use App\Models\Modules\Ticket\TicketCategory;
use App\Models\Modules\Ticket\TicketComment;
use App\Models\Modules\Ticket\TicketSlaSettings;
use App\Services\Modules\Ticket\TicketNumberService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ITSupportDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get all users and BUs
        $users = User::all();
        $businessUnits = BusinessUnit::whereNotNull('parent_id')->get(); // child BUs only

        if ($users->isEmpty() || $businessUnits->isEmpty()) {
            $this->command->warn('No users or business units found. Skipping demo data.');
            return;
        }

        $numberService = app(TicketNumberService::class);
        $now = Carbon::now();
        $ticketCount = 0;

        // Sample ticket data — realistic IT support scenarios
        $ticketTemplates = [
            [
                'title' => 'Laptop tidak bisa connect WiFi',
                'description' => 'Laptop saya tidak bisa terkoneksi ke jaringan WiFi kantor sejak pagi ini. Sudah coba restart laptop dan forget network tapi tetap tidak bisa connect. Error message: "Can\'t connect to this network".',
                'priority' => 'high',
                'category_name' => 'Network Issue',
                'status' => 'done',
                'days_ago' => 25,
                'resolve_hours' => 4,
            ],
            [
                'title' => 'Tidak bisa login ke email Outlook',
                'description' => 'Saya tidak bisa login ke Outlook desktop app. Password sudah benar tapi terus muncul error "Authentication failed". Sudah coba clear credentials di Credential Manager tapi masih sama.',
                'priority' => 'high',
                'category_name' => 'Email Issue',
                'status' => 'done',
                'days_ago' => 22,
                'resolve_hours' => 2,
            ],
            [
                'title' => 'Request instalasi Adobe Acrobat Pro',
                'description' => 'Mohon diinstalkan Adobe Acrobat Pro di laptop saya untuk kebutuhan edit dan sign PDF dokumen kontrak. Laptop: Dell Latitude 5540, Asset Tag: WNS-LT-0234.',
                'priority' => 'low',
                'category_name' => 'Service Request',
                'status' => 'done',
                'days_ago' => 20,
                'resolve_hours' => 24,
            ],
            [
                'title' => 'Printer lantai 3 paper jam terus',
                'description' => 'Printer HP LaserJet di lantai 3 (dekat ruang meeting Garuda) sering paper jam. Sudah dibersihkan tapi masih sering macet. Sepertinya roller-nya sudah aus.',
                'priority' => 'medium',
                'category_name' => 'Hardware Issue',
                'status' => 'done',
                'days_ago' => 18,
                'resolve_hours' => 48,
            ],
            [
                'title' => 'VPN tidak bisa connect dari rumah',
                'description' => 'Saya WFH hari ini tapi VPN GlobalProtect tidak bisa connect. Stuck di "Connecting..." terus. Internet rumah normal, bisa browsing. Sudah coba restart laptop dan router.',
                'priority' => 'critical',
                'category_name' => 'Network Issue',
                'status' => 'done',
                'days_ago' => 15,
                'resolve_hours' => 1,
            ],
            [
                'title' => 'Monitor kedua tidak terdeteksi',
                'description' => 'Monitor external kedua saya (Dell 27") tidak terdeteksi oleh laptop setelah update Windows kemarin. Sudah coba ganti kabel HDMI dan restart, tetap tidak muncul di Display Settings.',
                'priority' => 'medium',
                'category_name' => 'Hardware Issue',
                'status' => 'done',
                'days_ago' => 14,
                'resolve_hours' => 3,
            ],
            [
                'title' => 'Reset password akun SAP',
                'description' => 'Mohon reset password akun SAP saya (user ID: pramuji.arif). Saya lupa password karena sudah lama tidak login.',
                'priority' => 'medium',
                'category_name' => 'Account Access',
                'status' => 'done',
                'days_ago' => 12,
                'resolve_hours' => 1,
            ],
            [
                'title' => 'Excel macro error setelah update Office',
                'description' => 'Macro VBA di file laporan bulanan saya error setelah Office di-update ke versi terbaru. Error: "Compile error: Can\'t find project or library". File ini critical untuk reporting.',
                'priority' => 'high',
                'category_name' => 'Software Issue',
                'status' => 'done',
                'days_ago' => 10,
                'resolve_hours' => 6,
            ],
            [
                'title' => 'Request akses folder shared Finance',
                'description' => 'Saya butuh akses read-only ke folder shared \\\\server\\finance\\reports untuk kebutuhan audit internal. Sudah diapprove oleh Head of Finance Pak Budi.',
                'priority' => 'low',
                'category_name' => 'Account Access',
                'status' => 'done',
                'days_ago' => 8,
                'resolve_hours' => 12,
            ],
            [
                'title' => 'Laptop sangat lambat, sering hang',
                'description' => 'Laptop saya (Lenovo ThinkPad T480, 3 tahun) sangat lambat belakangan ini. Buka Excel saja butuh 2 menit. Task Manager menunjukkan disk usage 100% terus. Mohon dicek apakah perlu upgrade SSD.',
                'priority' => 'medium',
                'category_name' => 'Hardware Issue',
                'status' => 'in_progress',
                'days_ago' => 5,
                'resolve_hours' => null,
            ],
            [
                'title' => 'Email tidak bisa kirim attachment besar',
                'description' => 'Saya tidak bisa mengirim email dengan attachment lebih dari 10MB via Outlook. Pesan error: "The attachment size exceeds the allowable limit". Saya perlu kirim file presentasi 25MB ke client.',
                'priority' => 'medium',
                'category_name' => 'Email Issue',
                'status' => 'in_progress',
                'days_ago' => 4,
                'resolve_hours' => null,
            ],
            [
                'title' => 'Request setup laptop baru untuk karyawan baru',
                'description' => 'Karyawan baru di divisi Marketing (Rina Susanti) akan mulai tanggal 5 Mei. Mohon disiapkan laptop dengan standar setup: Windows 11, Office 365, VPN, email, dan akses ke shared folder Marketing.',
                'priority' => 'low',
                'category_name' => 'Service Request',
                'status' => 'in_progress',
                'days_ago' => 3,
                'resolve_hours' => null,
            ],
            [
                'title' => 'Zoom meeting audio tidak keluar',
                'description' => 'Saat Zoom meeting, audio saya tidak keluar di speaker laptop. Sudah cek volume dan audio settings di Zoom, semuanya normal. Tapi lawan bicara tidak bisa dengar suara saya.',
                'priority' => 'high',
                'category_name' => 'Software Issue',
                'status' => 'waiting',
                'days_ago' => 2,
                'resolve_hours' => null,
            ],
            [
                'title' => 'Internet kantor lambat sejak pagi',
                'description' => 'Internet di lantai 2 sangat lambat sejak pagi ini. Speed test menunjukkan hanya 2 Mbps (biasanya 100 Mbps). Semua orang di lantai 2 mengalami hal yang sama.',
                'priority' => 'critical',
                'category_name' => 'Network Issue',
                'status' => 'waiting',
                'days_ago' => 1,
                'resolve_hours' => null,
            ],
            [
                'title' => 'Minta tambah RAM laptop',
                'description' => 'Laptop saya sering kehabisan memory saat buka banyak tab Chrome + Excel + SAP bersamaan. Saat ini RAM 8GB, mohon di-upgrade ke 16GB jika memungkinkan.',
                'priority' => 'low',
                'category_name' => 'Service Request',
                'status' => 'waiting',
                'days_ago' => 1,
                'resolve_hours' => null,
            ],
            [
                'title' => 'Antivirus expired, minta renewal',
                'description' => 'Antivirus di laptop saya (Kaspersky) sudah expired sejak minggu lalu. Muncul notifikasi "Protection is disabled" terus. Mohon dibantu renewal atau install antivirus yang baru.',
                'priority' => 'high',
                'category_name' => 'Software Issue',
                'status' => 'waiting',
                'days_ago' => 0,
                'resolve_hours' => null,
            ],
            [
                'title' => 'Keyboard laptop beberapa tombol tidak fungsi',
                'description' => 'Tombol "A", "S", dan "D" di keyboard laptop saya tidak berfungsi. Kemarin tidak sengaja ketumpahan air sedikit. Saat ini pakai keyboard external sebagai workaround.',
                'priority' => 'medium',
                'category_name' => 'Hardware Issue',
                'status' => 'waiting',
                'days_ago' => 0,
                'resolve_hours' => null,
            ],
            [
                'title' => 'Projector ruang meeting Elang mati',
                'description' => 'Projector di ruang meeting Elang (lantai 5) tidak mau nyala. Lampu indikator berkedip merah. Sudah coba cabut-pasang kabel power tapi tetap tidak nyala.',
                'priority' => 'medium',
                'category_name' => 'Hardware Issue',
                'status' => 'cancelled',
                'days_ago' => 7,
                'resolve_hours' => null,
            ],
        ];

        // Get IT Support admins for assignment
        $itAdmins = UserBusinessUnit::where('is_it_support_admin', true)
            ->where('is_active', true)
            ->pluck('user_id')
            ->unique()
            ->values();

        foreach ($businessUnits as $bu) {
            $buUsers = User::whereHas('businessUnits', function ($q) use ($bu) {
                $q->where('business_unit_id', $bu->id)->where('is_active', true);
            })->get();

            if ($buUsers->isEmpty()) {
                continue;
            }

            $buCategories = TicketCategory::where('business_unit_id', $bu->id)->get()->keyBy('name');
            $buDepartments = Department::where('business_unit_id', $bu->id)->get();

            foreach ($ticketTemplates as $template) {
                $requester = $buUsers->random();
                $category = $buCategories->get($template['category_name']);
                $department = $buDepartments->isNotEmpty() ? $buDepartments->random() : null;
                $createdAt = $now->copy()->subDays($template['days_ago'])->subHours(rand(0, 8));

                // Generate ticket number
                try {
                    $ticketNumber = $numberService->generateTicketNumber($bu->id);
                } catch (\Throwable $e) {
                    $ticketNumber = 'IT.' . $bu->code . '/' . $createdAt->format('Ym') . '/' . str_pad($ticketCount + 1, 3, '0', STR_PAD_LEFT);
                }

                $resolvedAt = null;
                if ($template['status'] === 'done' && $template['resolve_hours']) {
                    $resolvedAt = $createdAt->copy()->addHours($template['resolve_hours']);
                }

                $assignedTo = null;
                if (in_array($template['status'], ['in_progress', 'done'])) {
                    $assignedTo = $itAdmins->isNotEmpty() ? $itAdmins->random() : null;
                }

                $ticket = Ticket::create([
                    'business_unit_id' => $bu->id,
                    'ticket_number' => $ticketNumber,
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'requester_id' => $requester->id,
                    'department_id' => $department?->id,
                    'status' => $template['status'],
                    'priority' => $template['priority'],
                    'category_id' => $category?->id,
                    'assigned_to' => $assignedTo,
                    'created_by' => $requester->id,
                    'resolved_at' => $resolvedAt,
                    'created_at' => $createdAt,
                    'updated_at' => $resolvedAt ?? $createdAt,
                ]);

                // Add comments for in_progress and done tickets
                if (in_array($template['status'], ['in_progress', 'done']) && $assignedTo) {
                    TicketComment::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $assignedTo,
                        'content' => 'Terima kasih atas laporannya. Sedang kami proses.',
                        'is_private' => false,
                        'created_at' => $createdAt->copy()->addHours(1),
                        'updated_at' => $createdAt->copy()->addHours(1),
                    ]);
                }

                if ($template['status'] === 'done' && $assignedTo) {
                    TicketComment::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $assignedTo,
                        'content' => 'Masalah sudah diselesaikan. Silakan cek kembali dan hubungi kami jika masih ada kendala.',
                        'is_private' => false,
                        'created_at' => $resolvedAt ?? $createdAt->copy()->addHours(2),
                        'updated_at' => $resolvedAt ?? $createdAt->copy()->addHours(2),
                    ]);

                    // Add internal note
                    TicketComment::create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $assignedTo,
                        'content' => 'Internal note: Issue resolved. Root cause documented.',
                        'is_private' => true,
                        'created_at' => $resolvedAt ?? $createdAt->copy()->addHours(2),
                        'updated_at' => $resolvedAt ?? $createdAt->copy()->addHours(2),
                    ]);
                }

                $ticketCount++;
            }
        }

        $this->command->info("Demo tickets seeded: {$ticketCount} tickets created across {$businessUnits->count()} business unit(s).");
    }
}
