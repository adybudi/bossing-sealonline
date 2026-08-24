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
        Schema::create('server_access_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seal_server_id')->constrained('seal_servers')->onDelete('cascade');
            $table->string('code')->unique()->index();
            $table->string('label')->nullable();
            $table->string('duration_type')->default('permanent'); // 7_days, 14_days, 30_days, permanent, custom
            $table->integer('duration_days')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->string('active_session_token', 64)->nullable()->index();
            $table->timestamp('last_active_at')->nullable();
            $table->string('last_ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_access_keys');
    }
};
