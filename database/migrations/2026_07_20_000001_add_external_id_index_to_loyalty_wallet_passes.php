<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_wallet_passes', function (Blueprint $table) {
            // Every Apple PassKit web-service call authenticates via
            // where(platform, external_id); index it and enforce serial uniqueness.
            $table->unique(['platform', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_wallet_passes', function (Blueprint $table) {
            $table->dropUnique(['platform', 'external_id']);
        });
    }
};
