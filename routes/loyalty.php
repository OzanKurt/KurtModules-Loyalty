<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Loyalty\Http\Controllers\CardController;
use Kurt\Modules\Loyalty\Http\Controllers\TerminalController;
use Kurt\Modules\Loyalty\Http\Controllers\WalletController;

Route::group([
    'prefix' => config('loyalty.routes.prefix'),
    'domain' => config('loyalty.routes.domain'),
    'middleware' => config('loyalty.routes.middleware', ['web']),
    'as' => 'loyalty.',
], function () {
    $rateLimit = 'throttle:'.config('loyalty.routes.rate_limit', '30,1');

    Route::get('c/{token}', [CardController::class, 'show'])->name('card.show');
    Route::get('c/{token}/state', [CardController::class, 'state'])->name('card.state');

    Route::post('programs/{program:slug}/cards', [CardController::class, 'store'])
        ->middleware($rateLimit)->name('card.store');
    Route::post('c/{token}/claim', [CardController::class, 'claim'])
        ->middleware($rateLimit)->name('card.claim');
    Route::post('c/{token}/vouchers/{voucher}', [CardController::class, 'redeemVoucher'])
        ->middleware($rateLimit)->name('card.redeem-voucher');

    Route::get('c/{token}/apple-pass', [WalletController::class, 'apple'])->name('card.apple');
    Route::get('c/{token}/google-pass', [WalletController::class, 'google'])->name('card.google');

    Route::middleware(config('loyalty.staff.middleware', ['can:loyalty:staff']))
        ->prefix('terminal')
        ->as('terminal.')
        ->group(function () {
            Route::get('/', [TerminalController::class, 'index'])->name('index');
            Route::post('stamp', [TerminalController::class, 'stamp'])->name('stamp');
            Route::post('redeem', [TerminalController::class, 'redeem'])->name('redeem');
        });
});
