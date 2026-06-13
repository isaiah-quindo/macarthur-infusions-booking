<?php

return [

    // All slot maths happen in the clinic's named timezone (DST-safe);
    // timestamps are stored UTC.
    'clinic_timezone' => env('CLINIC_TIMEZONE', 'Australia/Sydney'),

    // How long a pending_payment booking holds its slot before being
    // swept to abandoned.
    'hold_minutes' => (int) env('BOOKING_HOLD_MINUTES', 10),

    // Customers can't book closer to now than this.
    'lead_time_hours' => (int) env('BOOKING_LEAD_TIME_HOURS', 24),

    // ...or further ahead than this.
    'max_advance_days' => (int) env('BOOKING_MAX_ADVANCE_DAYS', 60),

    // Slot start times are emitted on this grid.
    'slot_granularity_minutes' => (int) env('BOOKING_SLOT_GRANULARITY_MINUTES', 15),

    // Cleanup time added after every appointment.
    'buffer_minutes' => (int) env('BOOKING_BUFFER_MINUTES', 15),

    // Whether patients may choose to pay at the clinic instead of online.
    // Set false to require upfront card payment for every booking.
    'allow_pay_in_person' => (bool) env('BOOKING_ALLOW_PAY_IN_PERSON', true),

    'nurse_notification_email' => env('NURSE_NOTIFICATION_EMAIL', 'macarthurinfusions@outlook.com.au'),

    'clinic' => [
        'name' => 'Macarthur Infusions',
        'address' => 'Suite 1, 67 Jacaranda Ave, Bradbury NSW 2560',
        'phone' => '1300 205 970',
        'website' => 'https://macarthurinfusions.com.au',
        'cancellation_policy' => 'Please give us at least 24 hours notice if you need to cancel or reschedule — call 1300 205 970 and we will look after you.',
    ],

    'square' => [
        'access_token' => env('SQUARE_ACCESS_TOKEN'),
        'location_id' => env('SQUARE_LOCATION_ID'),
        'application_id' => env('SQUARE_APPLICATION_ID'),
        'environment' => env('SQUARE_ENVIRONMENT', 'sandbox'),
        // From the Square developer portal → Webhook subscription → Signature key.
        'webhook_signature_key' => env('SQUARE_WEBHOOK_SIGNATURE_KEY'),
        // The exact notification URL configured on the subscription (must match
        // for signature verification). Leave blank to use the request URL —
        // fine in dev but fragile behind a reverse proxy.
        'webhook_notification_url' => env('SQUARE_WEBHOOK_NOTIFICATION_URL'),
    ],

];
