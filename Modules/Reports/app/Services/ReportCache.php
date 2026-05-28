<?php

namespace Modules\Reports\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Thin wrapper around Laravel's cache that uses tag-based invalidation when
 * the underlying store supports it (Redis / Memcached) and degrades to a
 * direct query (no cache) when it doesn't (file / database / array). This
 * way reports work in dev (no Redis required) AND get the speed-up in prod
 * once REDIS is configured.
 */
class ReportCache
{
    /**
     * The set of cache tags currently invalidated. Used so reports calling
     * remember() in production hit Redis with the right tag set.
     */
    public const TAG_APPLICATIONS = 'reports:applications';

    public const TAG_PAYMENTS = 'reports:payments';

    public const TAG_REFUNDS = 'reports:refunds';

    public const TAG_MERIT = 'reports:merit';

    public const TAG_SEATS = 'reports:seats';

    public const TAG_DOCUMENTS = 'reports:documents';

    public const TAG_WITHDRAWALS = 'reports:withdrawals';

    public function remember(string $key, array $tags, int $ttlSeconds, \Closure $callback): mixed
    {
        if (! $this->supportsTags()) {
            // No-tag cache stores: skip the cache entirely so writes are reflected
            // immediately. Acceptable for small colleges; Redis flips this on.
            return $callback();
        }

        return Cache::tags($tags)->remember($key, $ttlSeconds, $callback);
    }

    public function flush(array $tags): void
    {
        if (! $this->supportsTags()) {
            return;
        }
        Cache::tags($tags)->flush();
    }

    public function supportsTags(): bool
    {
        $store = Cache::getStore();

        return $store instanceof \Illuminate\Cache\TaggableStore;
    }
}
