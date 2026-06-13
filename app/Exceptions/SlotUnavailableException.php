<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown inside a booking-creating transaction when, after acquiring the
 * advisory lock, the requested slot no longer fits — usually because another
 * booking landed first and we are now at capacity.
 */
class SlotUnavailableException extends RuntimeException
{
}
