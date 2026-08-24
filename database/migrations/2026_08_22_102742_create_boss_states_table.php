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
        Schema::create('boss_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seal_server_id')->constrained('seal_servers')->onDelete('cascade');
            $table->string('boss_key', 150)->index();
            $table->string('boss_name', 150);
            $table->string('map_name', 100);
            $table->integer('slot_index')->default(1);
            $table->string('status', 30)->default('UNKNOWN');
            $table->bigInteger('killed_at')->nullable();
            $table->bigInteger('target_respawn_at')->nullable();
            $table->integer('interval_minutes')->default(30);
            $table->timestamps();

            $table->unique(['seal_server_id', 'boss_key'], 'server_boss_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boss_states');
    }
};
