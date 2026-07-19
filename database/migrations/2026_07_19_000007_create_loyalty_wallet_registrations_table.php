<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_wallet_registrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('device_library_id');
            $table->string('push_token');
            $table->string('pass_serial')->index();
            $table->timestamps();
            $table->unique(['device_library_id', 'pass_serial']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_wallet_registrations');
    }
};
