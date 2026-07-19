<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Services;

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Loyalty\Enums\StampSource;
use Kurt\Modules\Loyalty\Events\VoucherIssued;
use Kurt\Modules\Loyalty\Events\VoucherRedeemed;
use Kurt\Modules\Loyalty\Exceptions\VoucherAlreadyRedeemedException;
use Kurt\Modules\Loyalty\Exceptions\VoucherExpiredException;
use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Models\Voucher;

final class VoucherService
{
    public function __construct(private readonly StampService $stamps) {}

    public function issue(
        Program $program,
        int $stamps = 1,
        ?StampSource $source = null,
        ?int $expiresInSeconds = null,
        ?string $issuedBy = null,
    ): Voucher {
        /** @var Voucher $voucher */
        $voucher = $program->vouchers()->create([
            'token' => bin2hex(random_bytes(20)),
            'stamps' => $stamps,
            'issued_by' => $issuedBy,
            'expires_at' => $expiresInSeconds !== null ? now()->addSeconds($expiresInSeconds) : null,
            'status' => 'pending',
        ]);

        event(new VoucherIssued($voucher, $source));

        return $voucher;
    }

    public function redeem(Voucher $voucher, Card $card): Card
    {
        return DB::transaction(function () use ($voucher, $card) {
            /** @var Voucher $voucher */
            $voucher = Voucher::query()->whereKey($voucher->getKey())->lockForUpdate()->firstOrFail();

            if ($voucher->status === 'redeemed') {
                throw new VoucherAlreadyRedeemedException('Voucher already redeemed.');
            }

            if (! $voucher->isRedeemable()) {
                throw new VoucherExpiredException('Voucher is expired or not redeemable.');
            }

            // First stamp respects the card cooldown; the rest of a multi-stamp
            // voucher belong to the same authorized grant, so they bypass it.
            $card = $this->stamps->add($card, StampSource::Api, $voucher);
            for ($i = 1; $i < (int) $voucher->stamps; $i++) {
                $card = $this->stamps->add($card, StampSource::Api, $voucher, bypassThrottle: true);
            }

            $voucher->forceFill([
                'status' => 'redeemed',
                'redeemed_at' => now(),
                'redeemed_by_card_id' => $card->getKey(),
            ])->save();

            event(new VoucherRedeemed($voucher, $card));

            return $card;
        });
    }
}
