<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class Idempotency
{
    /**
     * Extract the idempotency key from a request, if any.
     */
    public static function key(Request $request): ?string
    {
        $key = $request->header('Idempotency-Key') ?? $request->input('idempotency_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    /**
     * Atomically claim a key. Returns true the first time it is seen (proceed)
     * and false on a replay within the TTL (skip re-applying the action).
     */
    public static function claim(string $key): bool
    {
        $ttl = (int) config('loyalty.idempotency.ttl', 60);

        return Cache::add('loyalty:idem:'.sha1($key), true, $ttl);
    }
}
