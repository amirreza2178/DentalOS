<?php

namespace App\Actions\Scheduling;

use App\Models\Appointment;

final class CancelAppointmentResult
{
    public const OUTCOME_CANCELLED = 'cancelled';

    public const OUTCOME_ALREADY_CANCELLED = 'already_cancelled';

    public function __construct(
        public readonly Appointment $appointment,
        public readonly string $outcome,
    ) {}

    public function wasNewlyCancelled(): bool
    {
        return $this->outcome === self::OUTCOME_CANCELLED;
    }

    public function wasAlreadyCancelled(): bool
    {
        return $this->outcome === self::OUTCOME_ALREADY_CANCELLED;
    }
}
