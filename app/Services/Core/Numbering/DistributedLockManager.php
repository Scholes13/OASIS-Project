<?php

namespace App\Services\Core\Numbering;

use Illuminate\Support\Facades\Cache;

/**
 * Acquire and release the distributed lock used to serialise number
 * generation across concurrent requests.
 *
 * Locks are keyed by module + department + year + month so two different
 * sequences can be generated in parallel, but a single sequence stays
 * serialised. Cache failures translate to lock-acquisition failures so we
 * never silently produce duplicate numbers when the cache layer is down.
 */
class DistributedLockManager
{
    public function __construct(
        protected string $cachePrefix = 'numbering_',
        protected int $lockTimeout = 30,
    ) {}

    /**
     * Build the cache key for the (module, department, year, month) tuple.
     * Supports null month for yearly sequences and null department for
     * cross-department numbering.
     */
    public function getLockKey(int $moduleId, ?int $departmentId, int $year, ?int $month): string
    {
        $monthKey = $month ?? 'yearly';
        $deptKey = $departmentId ?? 'shared';

        return $this->cachePrefix."lock:{$moduleId}:{$deptKey}:{$year}:{$monthKey}";
    }

    /**
     * Acquire the distributed lock for the given key.
     *
     * Throws when the lock is already held or when the cache layer fails;
     * callers must NOT proceed with number generation when this throws.
     */
    public function acquireLock(string $lockKey): bool
    {
        try {
            $acquired = Cache::add($lockKey, time(), $this->lockTimeout);

            if (! $acquired) {
                throw new \RuntimeException('Lock is currently held by another process. Please try again.');
            }

            return true;
        } catch (\RuntimeException $e) {
            // Re-throw runtime exceptions (lock acquisition failures).
            throw $e;
        } catch (\Exception $e) {
            // CRITICAL: If cache system fails, do NOT proceed.
            // This prevents race conditions when cache is down.
            throw new \RuntimeException(
                'Unable to acquire lock due to cache system failure. Please try again later.',
                0,
                $e
            );
        }
    }

    /**
     * Release the distributed lock. Cache errors are swallowed so a brief
     * cache outage does not cascade into a request failure on the way out.
     */
    public function releaseLock(string $lockKey): void
    {
        try {
            Cache::forget($lockKey);
        } catch (\Exception $e) {
            // Ignore errors when releasing lock.
        }
    }

    /**
     * Run the callback while holding the distributed lock.
     *
     * @template TReturn
     *
     * @param  \Closure(): TReturn  $callback
     * @return TReturn
     */
    public function withLock(string $lockKey, \Closure $callback): mixed
    {
        $lockAcquired = $this->acquireLock($lockKey);

        if (! $lockAcquired) {
            throw new \Exception('Unable to acquire lock for number generation. Please try again.');
        }

        try {
            return $callback();
        } finally {
            $this->releaseLock($lockKey);
        }
    }
}
