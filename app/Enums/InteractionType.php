<?php

namespace App\Enums;

enum InteractionType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Note = 'note';

    public function label(): string
    {
        return match($this) {
            self::Call => 'Call',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::Note => 'Note',
        };
    }
}
