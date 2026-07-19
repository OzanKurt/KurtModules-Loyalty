<?php

declare(strict_types=1);

it('boots the package and loads config', function () {
    expect(config('loyalty.identity_mode'))->toBe('anonymous_claimable');
});
