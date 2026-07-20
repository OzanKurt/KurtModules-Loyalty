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
     * Atomically claim a key within a scope. Returns true the first time the
     * (scope, key) pair is seen (proceed) and false on a replay within the TTL.
     * The scope (e.g. "stamp:{cardId}") keeps distinct actions/cards from
     * colliding when a client reuses one Idempotency-Key across requests.
     */
    public static function claim(string $key, string $scope = ''): bool
    {
        $ttl = (int) config('loyalty.idempotency.ttl', 60);

        return Cache::add('loyalty:idem:'.sha1($scope.'|'.$key), true, $ttl);
    }
}
