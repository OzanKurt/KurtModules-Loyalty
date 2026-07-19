<?php

declare(strict_types=1);

use Kurt\Modules\Loyalty\Models\Card;
use Kurt\Modules\Loyalty\Models\Program;

it('creates a program with a card and relationships resolve', function () {
    $program = Program::factory()->create(['stamps_required' => 7]);
    $card = Card::factory()->for($program)->create();

    expect($card->program->is($program))->toBeTrue()
        ->and($program->cards()->count())->toBe(1)
        ->and($card->stamps_count)->toBe(0)
        ->and($card->isComplete())->toBeFalse();
});

it('casts translatable name and reward', function () {
    $program = Program::factory()->create();
    $program->setTranslation('name', 'en', 'Coffee Club')->save();

    expect($program->getTranslation('name', 'en'))->toBe('Coffee Club');
});

it('reports rewards available', function () {
    $card = Card::factory()->create(['rewards_earned' => 2, 'rewards_redeemed' => 1]);

    expect($card->rewardsAvailable())->toBe(1);
});
