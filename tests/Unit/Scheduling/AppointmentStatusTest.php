<?php

namespace Tests\Unit\Scheduling;

use App\Enums\AppointmentStatus;
use PHPUnit\Framework\TestCase;

class AppointmentStatusTest extends TestCase
{
    public function test_resource_holding_and_terminal_transitions_match_the_documented_lifecycle(): void
    {
        $this->assertTrue(AppointmentStatus::PendingApproval->occupiesResources());
        $this->assertTrue(AppointmentStatus::Confirmed->occupiesResources());
        $this->assertTrue(AppointmentStatus::CheckedIn->occupiesResources());
        $this->assertTrue(AppointmentStatus::InProgress->occupiesResources());

        $this->assertFalse(AppointmentStatus::Draft->occupiesResources());
        $this->assertFalse(AppointmentStatus::Cancelled->occupiesResources());
        $this->assertFalse(AppointmentStatus::NoShow->occupiesResources());
        $this->assertFalse(AppointmentStatus::Completed->occupiesResources());
        $this->assertFalse(AppointmentStatus::Rescheduled->occupiesResources());

        $this->assertTrue(AppointmentStatus::Confirmed->canTransitionTo(AppointmentStatus::CheckedIn));
        $this->assertTrue(AppointmentStatus::Confirmed->canTransitionTo(AppointmentStatus::Cancelled));
        $this->assertTrue(AppointmentStatus::Confirmed->canTransitionTo(AppointmentStatus::Rescheduled));
        $this->assertFalse(AppointmentStatus::Confirmed->canTransitionTo(AppointmentStatus::Completed));
        $this->assertFalse(AppointmentStatus::Draft->canTransitionTo(AppointmentStatus::CheckedIn));
        $this->assertTrue(AppointmentStatus::PendingApproval->canTransitionTo(AppointmentStatus::Confirmed));
        $this->assertTrue(AppointmentStatus::InProgress->canTransitionTo(AppointmentStatus::Completed));
        $this->assertFalse(AppointmentStatus::Cancelled->canTransitionTo(AppointmentStatus::Confirmed));
    }
}
