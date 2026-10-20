<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Scheduled => 'indigo',
            self::Completed => 'emerald',
            self::Cancelled => 'rose',
        };
    }

    /** A booking holds its slot only while Scheduled. */
    public function isActive(): bool
    {
        return $this === self::Scheduled;
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /** @return list<self> */
    private function allowedTransitions(): array
    {
        return match ($this) {
            self::Scheduled => [self::Completed, self::Cancelled],
            self::Cancelled => [self::Scheduled],   // re-open (slot is re-checked for overlap)
            self::Completed => [],                  // terminal
        };
    }
}
