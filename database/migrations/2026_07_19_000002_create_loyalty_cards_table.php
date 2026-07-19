<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_cards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->string('token', 32)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->unsignedInteger('stamps_count')->default(0);
            $table->unsignedInteger('rewards_earned')->default(0);
            $table->unsignedInteger('rewards_redeemed')->default(0);
            $table->string('status')->default('active');
            $table->timestamp('last_stamped_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_cards');
    }
};
