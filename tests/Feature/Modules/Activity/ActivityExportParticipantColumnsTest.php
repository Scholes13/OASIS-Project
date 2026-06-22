<?php

namespace Tests\Feature\Modules\Activity;

use App\Models\Core\BusinessUnit;
use App\Models\Core\Department;
use App\Models\Core\Position;
use App\Models\Core\User;
use App\Models\Core\UserBusinessUnit;
use App\Models\Modules\Activity\ActivityType;
use App\Models\Modules\Activity\EmployeeTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ActivityExportParticipantColumnsTest extends TestCase
{
    use RefreshDatabase;

    protected User $viewer;

    protected User $memberA;

    protected User $memberB;

    protected BusinessUnit $bu;

    protected Department $dept;

    protected Department $otherDept;

    protected Position $position;

    protected ActivityType $activityType;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.ensure_pages_exist' => false]);

        $this->bu = BusinessUnit::create([
            'name' => 'Test BU',
            'code' => 'TBU',
            'is_active' => true,
        ]);

        $this->dept = Department::create([
            'business_unit_id' => $this->bu->id,
            'name' => 'Test Department',
            'code' => 'TDP',
            'is_active' => true,
        ]);

        $this->otherDept = Department::create([
            'business_unit_id' => $this->bu->id,
            'name' => 'Other Department',
            'code' => 'ODP',
            'is_active' => true,
        ]);

        $this->position = Position::create([
            'department_id' => $this->dept->id,
            'name' => 'Staff',
            'code' => 'STAFF',
            'level' => 'staff',
            'access_level' => 'staff',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);

        $this->viewer = $this->createUserWithAssignment('Viewer User', 'viewer@example.test', $this->dept, $this->position);
        $this->memberA = $this->createUserWithAssignment('Member A', 'member-a@example.test', $this->dept, $this->position);
        $this->memberB = $this->createUserWithAssignment('Member B', 'member-b@example.test', $this->dept, $this->position);

        $this->activityType = ActivityType::create([
            'code' => 'PLAN',
            'name' => 'Planning',
            'color' => '#16599c',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->dept->activityTypes()->attach($this->activityType->id, [
            'is_default' => true,
            'sort_order' => 1,
        ]);

        session([
            'current_business_unit_id' => $this->bu->id,
            'current_department_id' => $this->dept->id,
        ]);
    }

    public function test_export_detail_sheet_includes_participant_columns(): void
    {
        $task = $this->createTask($this->viewer, [
            'task_title' => 'Test task',
            'task_date' => now()->toDateString(),
        ]);
        $task->participants()->attach($this->memberA->id, ['joined_at' => now()]);
        $task->participants()->attach($this->memberB->id, ['joined_at' => now()]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', ['scope' => 'department']));

        $response->assertOk();

        $headers = $this->detailSheetHeaders($response->streamedContent());

        $this->assertSame([
            'No',
            'Tanggal',
            'Judul Aktivitas',
            'Deskripsi',
            'Ringkasan Aktivitas',
            'Kategori',
            'Sub Kategori',
            'Status',
            'Prioritas',
            'Pembuat',
            'Departemen',
            'Jatuh Tempo',
            'Mulai',
            'Selesai',
            'Durasi (menit)',
            'Catatan',
            'Jumlah Participant',
            'Daftar Participant',
            'Participant IDs',
        ], $headers);
    }

    public function test_export_data_mentah_sheet_includes_participant_columns(): void
    {
        $task = $this->createTask($this->viewer, [
            'task_title' => 'Test task',
            'task_date' => now()->toDateString(),
        ]);
        $task->participants()->attach($this->memberA->id, ['joined_at' => now()]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', ['scope' => 'department']));

        $response->assertOk();

        $headers = $this->rawDataSheetHeaders($response->streamedContent());

        $this->assertSame([
            'id_tugas',
            'tanggal_tugas',
            'judul_aktivitas',
            'deskripsi_aktivitas',
            'ringkasan_aktivitas',
            'kategori',
            'sub_kategori',
            'status',
            'prioritas',
            'nama_pembuat',
            'nama_departemen',
            'jatuh_tempo',
            'waktu_mulai',
            'waktu_selesai',
            'durasi_menit',
            'catatan',
            'jumlah_participant',
            'daftar_participant',
            'participant_ids',
        ], $headers);
    }

    public function test_export_data_mentah_sheet_participant_columns_correct_for_task_with_multiple_participants(): void
    {
        $task = $this->createTask($this->viewer, [
            'task_title' => 'Raw multi participant task',
            'task_date' => now()->toDateString(),
        ]);
        $task->participants()->attach($this->memberA->id, ['joined_at' => now()]);
        $task->participants()->attach($this->memberB->id, ['joined_at' => now()]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', ['scope' => 'department']));

        $response->assertOk();

        $row = $this->rawDataSheetDataRow($response->streamedContent(), 'Raw multi participant task');

        $this->assertSame('2', $row[16] ?? '0');
        $this->assertSame('Member A, Member B', $row[17] ?? '');
        $expectedIds = collect([$this->memberA->id, $this->memberB->id])->sort()->join('|');
        $this->assertSame($expectedIds, $row[18] ?? '');
    }

    public function test_export_detail_sheet_participant_columns_correct_for_task_without_participants(): void
    {
        $task = $this->createTask($this->memberA, [
            'task_title' => 'Creator only task',
            'task_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', ['scope' => 'department']));

        $response->assertOk();

        $row = $this->detailSheetDataRow($response->streamedContent(), 'Creator only task');

        // Columns Q (17) = Jumlah Participant, R (18) = Daftar Participant, S (19) = Participant IDs
        $this->assertSame('0', $row[16] ?? '0');
        $this->assertSame('', $row[17] ?? '');
        $this->assertSame('', $row[18] ?? '');
    }

    public function test_export_detail_sheet_participant_columns_correct_for_task_with_multiple_participants(): void
    {
        $task = $this->createTask($this->viewer, [
            'task_title' => 'Multi participant task',
            'task_date' => now()->toDateString(),
        ]);
        $task->participants()->attach($this->memberA->id, ['joined_at' => now()]);
        $task->participants()->attach($this->memberB->id, ['joined_at' => now()]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', ['scope' => 'department']));

        $response->assertOk();

        $row = $this->detailSheetDataRow($response->streamedContent(), 'Multi participant task');

        $this->assertSame('2', $row[16] ?? '0');
        $this->assertSame('Member A, Member B', $row[17] ?? '');
        $expectedIds = collect([$this->memberA->id, $this->memberB->id])->sort()->join('|');
        $this->assertSame($expectedIds, $row[18] ?? '');
    }

    public function test_export_scope_my_includes_creator_and_participant_tasks(): void
    {
        $taskAsCreator = $this->createTask($this->viewer, [
            'task_title' => 'Task as creator',
            'task_date' => now()->toDateString(),
        ]);

        $taskAsParticipant = $this->createTask($this->memberA, [
            'task_title' => 'Task as participant',
            'task_date' => now()->toDateString(),
        ]);
        $taskAsParticipant->participants()->attach($this->viewer->id, ['joined_at' => now()]);

        $this->createTask($this->memberA, [
            'task_title' => 'Other task',
            'task_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', ['scope' => 'my']));

        $response->assertOk();

        $detailRows = $this->detailSheetRows($response->streamedContent());

        $this->assertContains('Task as creator', $detailRows);
        $this->assertContains('Task as participant', $detailRows);
        $this->assertNotContains('Other task', $detailRows);
        $this->assertCount(2, $detailRows);
    }

    public function test_export_scope_my_creator_only_task_shows_empty_participants(): void
    {
        $taskAsCreator = $this->createTask($this->viewer, [
            'task_title' => 'My creator task',
            'task_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', ['scope' => 'my']));

        $response->assertOk();

        $row = $this->detailSheetDataRow($response->streamedContent(), 'My creator task');

        $this->assertSame('0', $row[16] ?? '0');
        $this->assertSame('', $row[17] ?? '');
        $this->assertSame('', $row[18] ?? '');
    }

    public function test_export_scope_my_includes_tasks_from_other_departments_within_same_business_unit(): void
    {
        $crossDepartmentTask = $this->createTask($this->viewer, [
            'department_id' => $this->otherDept->id,
            'task_title' => 'Cross department creator task',
            'task_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', ['scope' => 'my']));

        $response->assertOk();

        $detailRows = $this->detailSheetRows($response->streamedContent());

        $this->assertContains('Cross department creator task', $detailRows);
    }

    public function test_export_scope_department_member_focus_includes_creator_or_participant(): void
    {
        $creatorTask = $this->createTask($this->memberA, [
            'task_title' => 'Creator task',
            'task_date' => now()->toDateString(),
        ]);

        $participantTask = $this->createTask($this->viewer, [
            'task_title' => 'Participant task',
            'task_date' => now()->toDateString(),
        ]);
        $participantTask->participants()->attach($this->memberA->id, ['joined_at' => now()]);

        $bothTask = $this->createTask($this->memberA, [
            'task_title' => 'Both task',
            'task_date' => now()->toDateString(),
        ]);
        $bothTask->participants()->attach($this->memberA->id, ['joined_at' => now()]);

        $response = $this->actingAs($this->viewer)
            ->get(route('activity.task.export', [
                'scope' => 'department',
                'member_user_id' => (string) $this->memberA->id,
            ]));

        $response->assertOk();

        $detailRows = $this->detailSheetRows($response->streamedContent());

        $this->assertContains('Creator task', $detailRows);
        $this->assertContains('Participant task', $detailRows);
        $this->assertContains('Both task', $detailRows);
        $this->assertCount(3, $detailRows);
    }

    protected function createUserWithAssignment(string $name, string $email, Department $department, Position $position): User
    {
        $user = User::create([
            'name' => $name,
            'username' => str_replace('@', '.', $email),
            'email' => $email,
            'phone_number' => '081234567890',
            'password' => bcrypt('password'),
            'primary_department_id' => $department->id,
            'primary_position_id' => $position->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        UserBusinessUnit::create([
            'user_id' => $user->id,
            'business_unit_id' => $this->bu->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'is_active' => true,
            'is_primary' => true,
        ]);

        return $user;
    }

    protected function createTask(User $creator, array $overrides = []): EmployeeTask
    {
        return EmployeeTask::create(array_merge([
            'business_unit_id' => $this->bu->id,
            'department_id' => $this->dept->id,
            'created_by' => $creator->id,
            'activity_type_id' => $this->activityType->id,
            'task_title' => 'Test task',
            'task_description' => 'Test description',
            'task_date' => now()->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'planned',
            'priority' => 'medium',
        ], $overrides));
    }

    protected function detailSheetHeaders(string $streamedContent): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'activity-export-');
        file_put_contents($tempFile, $streamedContent);

        $spreadsheet = IOFactory::load($tempFile);
        $sheet = $spreadsheet->getSheetByName('Detail');
        $headers = $sheet ? $sheet->toArray()[0] : [];

        @unlink($tempFile);

        return $headers;
    }

    protected function rawDataSheetHeaders(string $streamedContent): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'activity-export-');
        file_put_contents($tempFile, $streamedContent);

        $spreadsheet = IOFactory::load($tempFile);
        $sheet = $spreadsheet->getSheetByName('Data Mentah');
        $headers = $sheet ? $sheet->toArray()[0] : [];

        @unlink($tempFile);

        return $headers;
    }

    protected function detailSheetRows(string $streamedContent): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'activity-export-');
        file_put_contents($tempFile, $streamedContent);

        $spreadsheet = IOFactory::load($tempFile);
        $sheet = $spreadsheet->getSheetByName('Detail');
        $rows = $sheet ? $sheet->toArray() : [];

        @unlink($tempFile);

        return collect($rows)
            ->skip(1)
            ->pluck(2)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values()
            ->all();
    }

    protected function detailSheetDataRow(string $streamedContent, string $taskTitle): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'activity-export-');
        file_put_contents($tempFile, $streamedContent);

        $spreadsheet = IOFactory::load($tempFile);
        $sheet = $spreadsheet->getSheetByName('Detail');
        $rows = $sheet ? $sheet->toArray() : [];

        @unlink($tempFile);

        foreach ($rows as $row) {
            if (isset($row[2]) && $row[2] === $taskTitle) {
                return $row;
            }
        }

        return [];
    }

    protected function rawDataSheetDataRow(string $streamedContent, string $taskTitle): array
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'activity-export-');
        file_put_contents($tempFile, $streamedContent);

        $spreadsheet = IOFactory::load($tempFile);
        $sheet = $spreadsheet->getSheetByName('Data Mentah');
        $rows = $sheet ? $sheet->toArray() : [];

        @unlink($tempFile);

        foreach ($rows as $row) {
            if (isset($row[2]) && $row[2] === $taskTitle) {
                return $row;
            }
        }

        return [];
    }
}
