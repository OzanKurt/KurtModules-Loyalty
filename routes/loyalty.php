<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Kurt\Modules\Loyalty\Http\Controllers\AppleWebServiceController;
use Kurt\Modules\Loyalty\Http\Controllers\CardController;
use Kurt\Modules\Loyalty\Http\Controllers\StatsController;
use Kurt\Modules\Loyalty\Http\Controllers\TerminalController;
use Kurt\Modules\Loyalty\Http\Controllers\WalletController;

$mode = (string) config('loyalty.http.mode', 'ui');

Route::group([
    'prefix' => config('loyalty.routes.prefix'),
    'domain' => config('loyalty.routes.domain'),
    'middleware' => config('loyalty.routes.middleware', ['web']),
    'as' => 'loyalty.',
], function () use ($mode) {
    $rateLimit = 'throttle:'.config('loyalty.routes.rate_limit', '30,1');
    $staff = config('loyalty.staff.middleware', ['can:loyalty:staff']);
    // Staff terminal throttles via the shared Core limiter (budget lives in
    // `loyalty.http.rate_limit`; registered by the service provider).
    $terminal = array_merge((array) $staff, ['throttle:loyalty-api']);

    /*
    | API surface — JSON + resource endpoints. Registered in both 'api' and 'ui'.
    */
    Route::get('c/{token}/state', [CardController::class, 'state'])->name('card.state');

    Route::post('programs/{program:slug}/cards', [CardController::class, 'store'])
        ->middleware($rateLimit)->name('card.store');
    Route::post('c/{token}/claim', [CardController::class, 'claim'])
        ->middleware($rateLimit)->name('card.claim');
    Route::post('c/{token}/vouchers/{voucher}', [CardController::class, 'redeemVoucher'])
        ->middleware($rateLimit)->name('card.redeem-voucher');

    Route::get('c/{token}/apple-pass', [WalletController::class, 'apple'])->name('card.apple');
    Route::get('c/{token}/google-pass', [WalletController::class, 'google'])->name('card.google');

    Route::middleware($terminal)->prefix('terminal')->as('terminal.')->group(function () {
        Route::post('stamp', [TerminalController::class, 'stamp'])->name('stamp');
        Route::post('redeem', [TerminalController::class, 'redeem'])->name('redeem');
    });

    // Analytics JSON for a consumer dashboard. Behind the staff gate; available
    // in both 'api' and 'ui' modes (never headless — the group isn't loaded).
    Route::middleware($staff)->get('stats', [StatsController::class, 'index'])->name('stats');

    /*
    | UI surface — shipped HTML pages. Registered only in 'ui'.
    */
    if ($mode === 'ui') {
        Route::get('c/{token}', [CardController::class, 'show'])->name('card.show');

        Route::middleware($staff)->get('terminal', [TerminalController::class, 'index'])->name('terminal.index');
    }
});

/*
| Apple Wallet web service (PassKit) — used for live pass updates. Stateless:
| registered outside the `web` middleware group (no session/CSRF); auth is the
| ApplePass token header handled in the controller.
*/
Route::prefix(config('loyalty.routes.prefix').'/apple/v1')
    ->domain(config('loyalty.routes.domain'))
    ->as('loyalty.apple.')
    ->group(function () {
        Route::post('devices/{device}/registrations/{passType}/{serial}', [AppleWebServiceController::class, 'register'])->name('register');
        Route::delete('devices/{device}/registrations/{passType}/{serial}', [AppleWebServiceController::class, 'unregister'])->name('unregister');
        Route::get('devices/{device}/registrations/{passType}', [AppleWebServiceController::class, 'serials'])->name('serials');
        Route::get('passes/{passType}/{serial}', [AppleWebServiceController::class, 'pass'])->name('pass');
        Route::post('log', [AppleWebServiceController::class, 'log'])->name('log');
    });
