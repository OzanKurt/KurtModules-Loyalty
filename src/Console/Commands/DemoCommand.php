<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\CardService;

final class DemoCommand extends Command
{
    protected $signature = 'loyalty:demo {--stamps=7 : Stamps required for the demo program}';

    protected $description = 'Create a demo loyalty program and card, and print the card URL.';

    public function handle(CardService $cards): int
    {
        $program = Program::query()->create([
            'name' => ['en' => 'Demo Coffee Club'],
            'slug' => 'demo-'.Str::lower(Str::random(6)),
            'reward' => ['en' => 'A free drink of your choice'],
            'stamps_required' => (int) $this->option('stamps'),
            'theme' => 'coffee',
            'icon' => 'coffee',
        ]);

        $card = $cards->create($program);

        $this->info('Demo program created: '.$program->slug);
        $this->line('Card code: '.$card->code);

        if (Route::has('loyalty.card.show')) {
            $this->line('Card URL:  '.route('loyalty.card.show', $card->token));
        } else {
            $this->line('Card token: '.$card->token.' (UI routes disabled; http.mode is not "ui")');
        }

        return self::SUCCESS;
    }
}
