<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case ViewingScheduled = 'viewing_scheduled';
    case Negotiation = 'negotiation';
    case Closed = 'closed';

    public function label(): string
    {
        return match($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::ViewingScheduled => 'Viewing Scheduled',
            self::Negotiation => 'Negotiation',
            self::Closed => 'Closed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::New => 'indigo',
            self::Contacted => 'blue',
            self::ViewingScheduled => 'amber',
            self::Negotiation => 'orange',
            self::Closed => 'zinc',
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions());
    }

    private function allowedTransitions(): array
    {
        return match($this) {
            self::New => [self::Contacted, self::Closed],
            self::Contacted => [self::ViewingScheduled, self::Closed],
            self::ViewingScheduled => [self::Negotiation, self::Contacted, self::Closed],
            self::Negotiation => [self::Closed, self::ViewingScheduled],
            self::Closed => [],
        };
    }
}
