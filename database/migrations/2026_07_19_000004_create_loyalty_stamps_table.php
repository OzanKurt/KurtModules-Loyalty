<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_stamps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('card_id')->constrained('loyalty_cards')->cascadeOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('loyalty_vouchers')->nullOnDelete();
            $table->string('source')->default('manual');
            $table->string('granted_by')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_stamps');
    }
};
