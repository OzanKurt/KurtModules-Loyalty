<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('creates all loyalty tables', function () {
    $tables = [
        'loyalty_programs',
        'loyalty_cards',
        'loyalty_vouchers',
        'loyalty_stamps',
        'loyalty_redemptions',
        'loyalty_wallet_passes',
        'loyalty_wallet_registrations',
    ];

    foreach ($tables as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
});
