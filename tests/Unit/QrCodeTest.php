<?php

declare(strict_types=1);

use Kurt\Modules\Loyalty\Support\QrCode;

it('renders an inline svg qr code without an xml prolog', function () {
    $svg = QrCode::svg('AB865D70');

    expect($svg)->toStartWith('<svg')
        ->and($svg)->toContain('</svg>')
        ->and($svg)->not->toContain('<?xml');
});

it('encodes different values into different svgs', function () {
    expect(QrCode::svg('ONE'))->not->toBe(QrCode::svg('TWO'));
});
