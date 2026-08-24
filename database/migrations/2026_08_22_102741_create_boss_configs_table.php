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
        Schema::create('boss_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seal_server_id')->constrained('seal_servers')->onDelete('cascade');
            $table->string('boss_name', 150);
            $table->string('map_name', 100)->nullable();
            $table->integer('interval_minutes')->default(30);
            $table->boolean('is_auto_learned')->default(true);
            $table->timestamps();

            $table->unique(['seal_server_id', 'boss_name', 'map_name'], 'server_boss_map_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boss_configs');
    }
};
