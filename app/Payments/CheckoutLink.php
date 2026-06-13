<?php

namespace App\Payments;

final readonly class CheckoutLink
{
    public function __construct(
        public string $id,
        public string $orderId,
        public string $url,
        public array $raw,
    ) {}
}
