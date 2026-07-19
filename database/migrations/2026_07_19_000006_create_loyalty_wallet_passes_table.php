<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_wallet_passes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('card_id')->constrained('loyalty_cards')->cascadeOnDelete();
            $table->string('platform');
            $table->string('external_id')->nullable();
            $table->string('auth_token')->nullable();
            $table->timestamp('last_pushed_at')->nullable();
            $table->timestamps();
            $table->unique(['card_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_wallet_passes');
    }
};
