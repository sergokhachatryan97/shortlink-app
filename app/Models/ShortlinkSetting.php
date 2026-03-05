<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ShortlinkSetting extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'key';
    protected $fillable = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember('shortlink_settings', 300, fn () => self::all()->pluck('value', 'key')->toArray());
        return $settings[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        Cache::forget('shortlink_settings');
    }
}
