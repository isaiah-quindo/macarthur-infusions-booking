<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'slug', 'category', 'name', 'image_path', 'description',
        'included', 'benefits', 'faqs',
        'duration_minutes', 'price_cents', 'is_active', 'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'included' => 'array',
            'benefits' => 'array',
            'faqs' => 'array',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function priceFormatted(): string
    {
        return '$'.number_format($this->price_cents / 100, $this->price_cents % 100 === 0 ? 0 : 2);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('supabase')->url($this->image_path);
    }
}
