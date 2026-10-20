<?php

namespace Tests\Unit;

use App\Enums\InquiryStatus;
use PHPUnit\Framework\TestCase;

class InquiryStatusTest extends TestCase
{
    public function test_new_can_advance_to_contacted_or_closed(): void
    {
        $this->assertTrue(InquiryStatus::New->canTransitionTo(InquiryStatus::Contacted));
        $this->assertTrue(InquiryStatus::New->canTransitionTo(InquiryStatus::Closed));
    }

    public function test_new_cannot_skip_to_negotiation(): void
    {
        $this->assertFalse(InquiryStatus::New->canTransitionTo(InquiryStatus::Negotiation));
        $this->assertFalse(InquiryStatus::New->canTransitionTo(InquiryStatus::ViewingScheduled));
    }

    public function test_closed_is_terminal(): void
    {
        foreach (InquiryStatus::cases() as $status) {
            $this->assertFalse(
                InquiryStatus::Closed->canTransitionTo($status),
                "Closed should not transition to {$status->value}"
            );
        }
    }

    public function test_viewing_scheduled_can_move_forward_or_back(): void
    {
        $this->assertTrue(InquiryStatus::ViewingScheduled->canTransitionTo(InquiryStatus::Negotiation));
        $this->assertTrue(InquiryStatus::ViewingScheduled->canTransitionTo(InquiryStatus::Contacted));
        $this->assertFalse(InquiryStatus::ViewingScheduled->canTransitionTo(InquiryStatus::New));
    }
}
