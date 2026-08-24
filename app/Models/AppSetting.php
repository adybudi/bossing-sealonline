<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Get a setting by key with a fallback default.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        // Auto decode JSON/Boolean if applicable
        $val = $setting->value;
        if ($val === 'true' || $val === '1') return true;
        if ($val === 'false' || $val === '0') return false;

        $json = json_decode($val, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return $val;
    }

    /**
     * Set/Update a setting by key.
     */
    public static function set(string $key, $value, ?string $description = null): self
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        return static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'description' => $description]
        );
    }

    /**
     * Check if public users are required to enter an access code.
     * Default: false (Direct public server selection mode).
     */
    public static function isAccessCodeRequired(): bool
    {
        return (bool) static::get('require_access_code', false);
    }
}
