<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_programs', function (Blueprint $table) {
            // null = fall back to the config default (which itself defaults to
            // "never expires"); a value overrides the default per program.
            $table->unsignedInteger('stamp_expiry_days')->nullable()->after('reset_on_reward');
            $table->unsignedInteger('reward_expiry_days')->nullable()->after('stamp_expiry_days');
        });

        Schema::table('loyalty_cards', function (Blueprint $table) {
            // Earned rewards voided by the expiry prune. Kept separate from
            // rewards_redeemed so redemption analytics stay accurate.
            $table->unsignedInteger('rewards_expired')->default(0)->after('rewards_redeemed');
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_programs', function (Blueprint $table) {
            $table->dropColumn(['stamp_expiry_days', 'reward_expiry_days']);
        });

        Schema::table('loyalty_cards', function (Blueprint $table) {
            $table->dropColumn('rewards_expired');
        });
    }
};
