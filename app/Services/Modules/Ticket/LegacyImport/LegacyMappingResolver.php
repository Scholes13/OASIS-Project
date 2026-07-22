<?php

namespace App\Services\Modules\Ticket\LegacyImport;

use App\Models\Core\Department;
use App\Models\Core\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

final class LegacyMappingResolver
{
    /** @var array<int, int|null> */
    private array $staffUsers = [];

    /** @var array<int, int|null> */
    private array $commentUsers = [];

    /** @var array<string, Department> */
    private array $departments = [];

    public function preload(ConnectionInterface $legacy): void
    {
        foreach ($legacy->table('staff')->select('id', 'email')->get() as $staff) {
            $this->staffUsers[(int) $staff->id] = $this->userIdByEmail($staff->email);
        }

        foreach ($legacy->table('users')->select('id', 'email')->get() as $user) {
            $this->commentUsers[(int) $user->id] = $this->userIdByEmail($user->email);
        }
    }

    public function requester(object $ticket, LegacyImportOptions $options, LegacyImportReport $report): User
    {
        $user = User::query()->where('email', $ticket->requester_email)->first();
        if ($user !== null) {
            return $user;
        }

        $report->increment('missing_requesters');

        return $options->fallbackUser;
    }

    public function assignee(object $ticket, LegacyImportOptions $options, LegacyImportReport $report): ?User
    {
        if (empty($ticket->assigned_to)) {
            return null;
        }

        $userId = $this->staffUsers[(int) $ticket->assigned_to] ?? null;
        if ($userId !== null && ($user = User::query()->find($userId)) !== null) {
            return $user;
        }

        $report->increment('missing_assignees');

        return $options->fallbackUser;
    }

    public function commentUserId(?int $legacyUserId, LegacyImportOptions $options, LegacyImportReport $report): int
    {
        $userId = $legacyUserId === null ? null : ($this->commentUsers[$legacyUserId] ?? null);
        if ($userId === null) {
            $report->increment('missing_comment_users');
        }

        return $userId ?? (int) $options->fallbackUser->id;
    }

    public function department(?string $value, LegacyImportOptions $options, LegacyImportReport $report): Department
    {
        $key = Str::upper(trim((string) $value));
        if ($key !== '' && isset($this->departments[$key])) {
            return $this->departments[$key];
        }

        $department = $key === '' ? null : Department::query()
            ->where('business_unit_id', $options->businessUnit->id)
            ->where(function ($query) use ($key) {
                $query->whereRaw('UPPER(code) = ?', [$key])
                    ->orWhereRaw('UPPER(name) = ?', [$key]);
            })
            ->first();

        if ($department === null) {
            $report->increment('missing_departments');
            $department = $options->fallbackDepartment;
        }

        if ($key !== '') {
            $this->departments[$key] = $department;
        }

        return $department;
    }

    private function userIdByEmail(?string $email): ?int
    {
        return empty($email) ? null : User::query()->where('email', $email)->value('id');
    }
}
