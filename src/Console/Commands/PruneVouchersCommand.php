<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Console\Commands;

use Illuminate\Console\Command;
use Kurt\Modules\Loyalty\Models\Voucher;

final class PruneVouchersCommand extends Command
{
    protected $signature = 'loyalty:prune-vouchers';

    protected $description = 'Mark expired pending vouchers as expired.';

    public function handle(): int
    {
        $count = Voucher::query()
            ->where('status', 'pending')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} voucher(s).");

        return self::SUCCESS;
    }
}
