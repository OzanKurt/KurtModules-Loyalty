<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_programs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->nullableMorphs('owner');
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('reward');
            $table->unsignedSmallInteger('stamps_required')->default(10);
            $table->string('theme')->default('coffee');
            $table->string('icon')->default('coffee');
            $table->unsignedInteger('cooldown_seconds')->default(30);
            $table->unsignedInteger('max_per_day')->nullable();
            $table->boolean('reset_on_reward')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_programs');
    }
};
