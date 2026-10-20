<?php

namespace App\Enums;

enum PropertyType: string
{
    case Apartment = 'apartment';
    case House = 'house';
    case Villa = 'villa';
    case Land = 'land';
    case Commercial = 'commercial';
    case Office = 'office';

    public function label(): string
    {
        return match($this) {
            self::Apartment => 'Apartment',
            self::House => 'House',
            self::Villa => 'Villa',
            self::Land => 'Land',
            self::Commercial => 'Commercial',
            self::Office => 'Office',
        };
    }
}
