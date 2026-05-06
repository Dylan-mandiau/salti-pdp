<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class QseInterlocutor extends Model
{
    protected $table = 'qse_interlocutors';

    protected $fillable = [
        'name', 'role', 'phone', 'email', 'is_main', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_main' => 'boolean'];
    }

    /**
     * Liste ordonnée pour affichage PDF + admin.
     * Cache 10 min : la donnée bouge très rarement.
     */
    public static function listForPdf(): \Illuminate\Support\Collection
    {
        return Cache::remember('qse_interlocutors:list', 600, function () {
            return self::orderBy('sort_order')->orderBy('id')->get();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget('qse_interlocutors:list');
    }

    protected static function booted(): void
    {
        static::saved(fn() => self::clearCache());
        static::deleted(fn() => self::clearCache());
    }
}
