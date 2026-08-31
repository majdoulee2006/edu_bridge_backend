<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getSetting(string $key, ?string $default = null): ?string
    {
        try {
            return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
                $setting = static::where('key', $key)->first();
                return $setting ? $setting->value : $default;
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function setSetting(string $key, ?string $value): void
    {
        try {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
            Cache::forget("system_setting_{$key}");
            Cache::forget('system_theme_settings');
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }

    public static function getThemeSettings(): array
    {
        try {
            return Cache::remember('system_theme_settings', 3600, function () {
                $settings = static::pluck('value', 'key')->all();
                return [
                    'primary_color' => $settings['primary_color'] ?? '#f2f20d',
                    'accent_name'   => $settings['accent_name'] ?? 'gold',
                    'theme_mode'    => $settings['theme_mode'] ?? 'dark',
                ];
            });
        } catch (\Throwable $e) {
            return [
                'primary_color' => '#f2f20d',
                'accent_name'   => 'gold',
                'theme_mode'    => 'dark',
            ];
        }
    }
}
