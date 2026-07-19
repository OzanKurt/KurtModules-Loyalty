<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Events;

use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Models\Voucher;

final class VoucherIssued
{
    public function __construct(
        public readonly Voucher $voucher,
        public readonly ?StampSource $source = null,
    ) {}
}
