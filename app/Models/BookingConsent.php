<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingConsent extends Model
{
    protected $fillable = [
        'booking_id', 'privacy_policy_version', 'collection_notice_version',
        'consented_at', 'consent_ip', 'consent_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'immutable_datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
