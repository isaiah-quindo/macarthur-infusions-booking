<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Abandoned = 'abandoned';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    /** Statuses that hold a slot (must match the DB exclusion constraint). */
    public static function blocking(): array
    {
        return [self::PendingPayment, self::Confirmed];
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment',
            self::Confirmed => 'Confirmed',
            self::Abandoned => 'Abandoned',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
            self::NoShow => 'No-show',
        };
    }
}
