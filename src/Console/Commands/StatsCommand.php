<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Kurt\Modules\Loyalty\Models\Program;
use Kurt\Modules\Loyalty\Services\LoyaltyStatsService;
use Symfony\Component\Console\Helper\TableSeparator;

final class StatsCommand extends Command
{
    protected $signature = 'loyalty:stats
        {--program= : Limit to one program (id or slug)}
        {--since= : Only count records created on/after this date}
        {--until= : Only count records created on/before this date}';

    protected $description = 'Print loyalty analytics: cards, stamps, and reward earn/redeem funnels.';

    public function handle(LoyaltyStatsService $stats): int
    {
        $programId = null;
        $programOption = $this->option('program');

        if (is_string($programOption) && $programOption !== '') {
            $program = $this->resolveProgram($programOption);

            if ($program === null) {
                $this->error('Program not found: '.$programOption);

                return self::FAILURE;
            }

            $programId = (int) $program->getKey();
        }

        $data = $stats->overview(
            $programId,
            $this->parseDate('since'),
            $this->parseDate('until'),
        );

        $rows = [];
        foreach ($data['programs'] as $row) {
            $rows[] = [
                $row['slug'],
                $row['cards_issued'],
                $row['active_cards'],
                $row['stamps_granted'],
                $row['rewards_earned'],
                $row['rewards_redeemed'],
                $this->percent($row['redemption_rate']),
            ];
        }

        $totals = $data['totals'];
        $rows[] = new TableSeparator;
        $rows[] = [
            '<info>TOTAL</info>',
            $totals['cards_issued'],
            $totals['active_cards'],
            $totals['stamps_granted'],
            $totals['rewards_earned'],
            $totals['rewards_redeemed'],
            $this->percent($totals['redemption_rate']),
        ];

        $this->table(
            ['Program', 'Cards', 'Active', 'Stamps', 'Earned', 'Redeemed', 'Redeem %'],
            $rows,
        );

        return self::SUCCESS;
    }

    private function resolveProgram(string $value): ?Program
    {
        return Program::query()
            ->where('slug', $value)
            ->when(ctype_digit($value), fn ($q) => $q->orWhere('id', (int) $value))
            ->first();
    }

    private function parseDate(string $option): ?Carbon
    {
        $value = $this->option($option);

        return is_string($value) && $value !== '' ? Carbon::parse($value) : null;
    }

    private function percent(float $rate): string
    {
        return number_format($rate * 100, 1).'%';
    }
}
