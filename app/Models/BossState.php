<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BossState extends Model
{
    use HasFactory;

    protected $fillable = [
        'seal_server_id',
        'boss_key',
        'boss_name',
        'map_name',
        'slot_index',
        'status',
        'killed_at',
        'target_respawn_at',
        'interval_minutes',
    ];

    protected $casts = [
        'slot_index' => 'integer',
        'killed_at' => 'integer',
        'target_respawn_at' => 'integer',
        'interval_minutes' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(SealServer::class, 'seal_server_id');
    }
}
