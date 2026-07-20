<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Support;

final class CardCredentials
{
    /**
     * Long, high-entropy secret used in the public card URL and QR.
     * 16 random bytes = 128 bits, hex-encoded to 32 chars.
     */
    public static function token(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Short, human-typeable code for counter/manual lookup. Uses a
     * Crockford-style alphabet (no 0/1/I/O/U) to avoid read-aloud ambiguity.
     * Not a security credential — it is only usable at the staff-gated terminal.
     */
    public static function code(int $length = 8): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTVWXYZ';
        $max = strlen($alphabet) - 1;

        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }
}
