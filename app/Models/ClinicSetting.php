<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row settings table. Use ClinicSetting::current() everywhere instead
 * of re-querying — the singleton is cached for the request, and writes
 * invalidate the cache.
 */
class ClinicSetting extends Model
{
    protected $fillable = ['concurrent_capacity', 'max_advance_days'];

    protected function casts(): array
    {
        return [
            'concurrent_capacity' => 'integer',
            'max_advance_days' => 'integer',
        ];
    }

    /**
     * Cheap per-request memo. We deliberately do NOT cache the model instance
     * across requests — serialized Eloquent models can come back as
     * __PHP_Incomplete_Class after class reloads, which has bitten us before.
     * One small query per request is fine; the slot calc only calls this once.
     */
    private static ?self $cached = null;

    public static function current(): self
    {
        return self::$cached ??= static::firstOrCreate([], ['concurrent_capacity' => 1]);
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::$cached = null);
        static::deleted(fn () => self::$cached = null);
    }
}
