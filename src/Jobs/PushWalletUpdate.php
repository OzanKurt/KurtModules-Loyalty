<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Wallet\WalletManager;

final class PushWalletUpdate implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Card $card) {}

    public function handle(WalletManager $wallet): void
    {
        if ($wallet->appleEnabled()) {
            $wallet->apple()->pushUpdate($this->card);
        }

        if ($wallet->googleEnabled()) {
            $wallet->google()->pushUpdate($this->card);
        }
    }
}
