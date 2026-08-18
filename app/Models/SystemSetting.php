<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getSetting(string $key, ?string $default = null): ?string
    {
        return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public static function setSetting(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("system_setting_{$key}");
        Cache::forget('system_theme_settings');
    }

    public static function getThemeSettings(): array
    {
        return Cache::remember('system_theme_settings', 3600, function () {
            $settings = static::pluck('value', 'key')->all();
            return [
                'primary_color' => $settings['primary_color'] ?? '#f2f20d',
                'accent_name'   => $settings['accent_name'] ?? 'gold',
                'theme_mode'    => $settings['theme_mode'] ?? 'dark',
            ];
        });
    }
}
