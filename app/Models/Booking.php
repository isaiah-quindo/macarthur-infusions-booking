<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'reference', 'service_id', 'starts_at', 'ends_at', 'status',
        'payment_method', 'customer_name', 'customer_email', 'customer_phone',
        'notes', 'hold_expires_at', 'reminder_sent_at',
        'payment_link_id', 'payment_link_order_id', 'payment_link_url',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'hold_expires_at' => 'immutable_datetime',
            'reminder_sent_at' => 'immutable_datetime',
            'status' => BookingStatus::class,
            'payment_method' => PaymentMethod::class,
            // PII at rest: encrypted with APP_KEY. The cast (en|de)crypts on
            // read/write so application code is unchanged. Trade-off: these
            // columns can't be indexed or WHERE-searched — admin lookups must
            // go via `reference` or load the row and compare in PHP.
            'customer_name' => 'encrypted',
            'customer_email' => 'encrypted',
            'customer_phone' => 'encrypted',
            'notes' => 'encrypted',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function consent(): HasOne
    {
        return $this->hasOne(BookingConsent::class);
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    /** e.g. MI-7K3F9P — unambiguous alphabet, no 0/O/1/I. */
    public static function generateReference(): string
    {
        do {
            $random = Str::upper(Str::password(6, letters: true, numbers: true, symbols: false));
            $random = strtr($random, ['0' => '2', 'O' => 'P', '1' => '3', 'I' => 'J', 'L' => 'M']);
            $reference = 'MI-'.$random;
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', BookingStatus::blocking());
    }

    /** True once a completed payment (online or in-person) is recorded. */
    public function isPaid(): bool
    {
        if ($this->relationLoaded('payments')) {
            return $this->payments->contains(fn ($p) => $p->status === Payment::STATUS_COMPLETED);
        }

        return $this->payments()
            ->where('status', Payment::STATUS_COMPLETED)
            ->exists();
    }

    public function holdHasExpired(): bool
    {
        return $this->status === BookingStatus::PendingPayment
            && $this->hold_expires_at !== null
            && $this->hold_expires_at->isPast();
    }

    public function startsAtClinic(): CarbonImmutable
    {
        return $this->starts_at->setTimezone(config('booking.clinic_timezone'));
    }

    public function endsAtClinic(): CarbonImmutable
    {
        return $this->ends_at->setTimezone(config('booking.clinic_timezone'));
    }
}
