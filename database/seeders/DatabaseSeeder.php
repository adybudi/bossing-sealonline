<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SealServer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin User
        User::firstOrCreate(
            ['email' => 'admin@seal.local'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password123'),
            ]
        );

        // Sample Seal Server
        SealServer::firstOrCreate(
            ['slug' => 'seal-server-demo'],
            [
                'name' => 'Seal Demo Server',
                'access_code' => 'seal_demo_access_code_123',
                'discord_token' => null,
                'discord_channel_id' => '000000000000000000',
                'is_active' => true,
                'bot_status' => 'STOPPED',
            ]
        );
    }
}
