<?php

namespace App\Services\Modules\CashflowProjection\Import;

use App\Models\Core\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashflowImportTokenService
{
    public function issue(array $rows, User $user, int $businessUnitId, int $contextYear, int $contextMonth): string
    {
        return Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'business_unit_id' => $businessUnitId,
            'context_year' => $contextYear,
            'context_month' => $contextMonth,
            'expires_at' => now()->addMinutes(20)->timestamp,
            'nonce' => (string) Str::uuid(),
            'rows_hash' => $this->hash($rows),
        ], JSON_THROW_ON_ERROR));
    }

    public function verify(string $token, array $rows, User $user, int $businessUnitId, int $contextYear, int $contextMonth): void
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['preview_token' => 'Preview import tidak valid. Upload ulang file.']);
        }

        if ((int) ($payload['user_id'] ?? 0) !== $user->id
            || (int) ($payload['business_unit_id'] ?? 0) !== $businessUnitId
            || (int) ($payload['context_year'] ?? 0) !== $contextYear
            || (int) ($payload['context_month'] ?? 0) !== $contextMonth
            || (int) ($payload['expires_at'] ?? 0) <= now()->timestamp
            || ! hash_equals((string) ($payload['rows_hash'] ?? ''), $this->hash($rows))) {
            throw ValidationException::withMessages(['preview_token' => 'Preview import berubah atau kedaluwarsa. Upload ulang file.']);
        }
    }

    public function claim(string $token, array $rows, User $user, int $businessUnitId, int $contextYear, int $contextMonth): string
    {
        $this->verify($token, $rows, $user, $businessUnitId, $contextYear, $contextMonth);
        $key = 'cashflow-import-token:'.hash('sha256', $token);

        if (! Cache::add($key, true, now()->addMinutes(20))) {
            throw ValidationException::withMessages(['preview_token' => 'Preview import sudah pernah diproses. Upload ulang file.']);
        }

        return $key;
    }

    public function release(string $claimKey): void
    {
        Cache::forget($claimKey);
    }

    private function hash(array $rows): string
    {
        return hash('sha256', json_encode($rows, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));
    }
}
