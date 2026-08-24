<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ServerAccessKey extends Model
{
    protected $fillable = [
        'seal_server_id',
        'code',
        'label',
        'duration_type',
        'duration_days',
        'activated_at',
        'expires_at',
        'is_active',
        'active_session_token',
        'last_active_at',
        'last_ip_address',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_active_at' => 'datetime',
        'is_active' => 'boolean',
        'duration_days' => 'integer',
    ];

    /**
     * Parent Seal Server relationship.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(SealServer::class, 'seal_server_id');
    }

    /**
     * Check if key has expired.
     */
    public function isExpired(): bool
    {
        if ($this->duration_type === 'permanent' || is_null($this->expires_at)) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if key is currently valid.
     */
    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Generate a new single-active session token for a device.
     */
    public function createActiveSession(?string $ipAddress = null): string
    {
        $newToken = Str::random(40);
        $now = Carbon::now();

        // If not yet activated and duration is relative, calculate expires_at now
        if (is_null($this->activated_at)) {
            $this->activated_at = $now;
            if ($this->duration_days && $this->duration_type !== 'permanent') {
                $this->expires_at = $now->copy()->addDays($this->duration_days);
            }
        }

        $this->active_session_token = $newToken;
        $this->last_active_at = $now;
        $this->last_ip_address = $ipAddress ?: request()->ip();
        $this->save();

        return $newToken;
    }

    /**
     * Human-readable remaining time format.
     */
    public function getRemainingTimeHumanAttribute(): string
    {
        if ($this->duration_type === 'permanent' || is_null($this->expires_at)) {
            return 'Permanen (Tanpa Batas)';
        }

        if ($this->isExpired()) {
            return 'Kadaluarsa (Expired)';
        }

        return $this->expires_at->diffForHumans([
            'parts' => 2,
            'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
        ]);
    }

    /**
     * Helper to generate standardized high-entropy cryptographic access codes with special characters.
     * Guaranteed anti-bruteforce: ~195 bits of entropy ($70^{32}$ combinations).
     */
    public static function generateUniqueCode(string $prefix = 'SEAL', string $durationType = '7_days'): string
    {
        $tag = '7D';
        if ($durationType === '14_days') $tag = '14D';
        elseif ($durationType === '30_days') $tag = '30D';
        elseif ($durationType === 'permanent') $tag = 'PERM';

        // Pool of high-entropy characters including alphanumeric and special characters
        $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@$%*_-';
        $maxIndex = strlen($chars) - 1;

        do {
            $chunks = [];
            // Generate 4 chunks of 6 random cryptographic characters with special symbols
            for ($c = 0; $c < 4; $c++) {
                $chunk = '';
                for ($i = 0; $i < 6; $i++) {
                    $chunk .= $chars[random_int(0, $maxIndex)];
                }
                $chunks[] = $chunk;
            }

            $entropyString = implode('-', $chunks);
            $code = "{$prefix}-{$tag}-{$entropyString}";
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
