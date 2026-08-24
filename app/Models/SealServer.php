<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SealServer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'access_code',
        'discord_token',
        'discord_channel_id',
        'is_active',
        'bot_status',
        'last_error',
        'last_screened_at',
    ];

    protected $casts = [
        'discord_token' => 'encrypted',
        'is_active' => 'boolean',
        'last_screened_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($server) {
            if (empty($server->slug)) {
                $server->slug = Str::slug($server->name) . '-' . Str::random(5);
            }
            if (empty($server->access_code)) {
                $server->access_code = self::generateAccessCode();
            }
        });
    }

    public static function generateAccessCode(): string
    {
        return 'seal_' . bin2hex(random_bytes(16));
    }

    public function configs(): HasMany
    {
        return $this->hasMany(BossConfig::class, 'seal_server_id');
    }

    public function states(): HasMany
    {
        return $this->hasMany(BossState::class, 'seal_server_id');
    }

    public function accessKeys(): HasMany
    {
        return $this->hasMany(ServerAccessKey::class, 'seal_server_id');
    }
}
