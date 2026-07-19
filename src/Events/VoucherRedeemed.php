<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Events;

use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Voucher;

final class VoucherRedeemed
{
    public function __construct(
        public readonly Voucher $voucher,
        public readonly Card $card,
    ) {}
}
