<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_program_tiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('program_id')->constrained('loyalty_programs')->cascadeOnDelete();
            $table->unsignedInteger('threshold');            // cumulative stamp count that earns this tier
            $table->string('reward');                        // human label for the tier's reward
            $table->json('reward_payload')->nullable();      // optional voucher-config / metadata
            $table->unsignedInteger('position')->default(0); // display / sort order
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['program_id', 'threshold']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_program_tiers');
    }
};
