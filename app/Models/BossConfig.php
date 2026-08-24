<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BossConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'seal_server_id',
        'boss_name',
        'map_name',
        'interval_minutes',
        'is_auto_learned',
    ];

    protected $casts = [
        'interval_minutes' => 'integer',
        'is_auto_learned' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(SealServer::class, 'seal_server_id');
    }
}
