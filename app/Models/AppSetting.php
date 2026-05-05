<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Réglages globaux de l'application.
 * Clés actuellement utilisées :
 *   - 'permis_feu_path' / 'permis_feu_filename'
 *   - 'convention_pret_path' / 'convention_pret_filename'
 *
 * Lecture/écriture via les helpers statiques pour cache automatique.
 */
class AppSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Récupère la valeur d'un réglage. Cache 5 min.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("app_setting:$key", 300, function () use ($key, $default) {
            return self::where('key', $key)->value('value') ?? $default;
        });
    }

    /**
     * Définit la valeur d'un réglage.
     */
    public static function set(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("app_setting:$key");
    }

    public static function forget(string $key): void
    {
        self::where('key', $key)->delete();
        Cache::forget("app_setting:$key");
    }
}
