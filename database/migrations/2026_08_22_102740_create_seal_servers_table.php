<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seal_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('access_code', 64)->unique()->index();
            $table->text('discord_token')->nullable();
            $table->string('discord_channel_id', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('bot_status')->default('STOPPED');
            $table->text('last_error')->nullable();
            $table->timestamp('last_screened_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seal_servers');
    }
};
