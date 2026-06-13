<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Card = 'card';        // paid online via Square
    case InPerson = 'in_person'; // paid at the clinic

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Card (online)',
            self::InPerson => 'Pay at clinic',
        };
    }
}
