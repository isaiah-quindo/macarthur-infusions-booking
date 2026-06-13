<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Email addresses that should receive clinic notifications (new bookings,
     * payment reviews). Only Admin users — super admins are intentionally
     * excluded (they're for setup/oversight and don't need every alert).
     * If no admin exists, fall back to the configured nurse address so
     * alerts are never silently lost.
     *
     * @return list<string>
     */
    public static function adminEmails(): array
    {
        $emails = static::where('role', UserRole::Admin)->pluck('email')->all();

        return $emails ?: array_values(array_filter([config('booking.nurse_notification_email')]));
    }
}
