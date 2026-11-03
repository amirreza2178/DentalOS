<?php

namespace App\Enums;

enum FollowUpStatus: string
{
    case Proposed = 'proposed';
    case OfferedToPatient = 'offered_to_patient';
    case Accepted = 'accepted';
    case Scheduled = 'scheduled';
    case Declined = 'declined';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function canBeScheduled(): bool
    {
        return in_array($this, [
            self::Proposed,
            self::OfferedToPatient,
            self::Accepted,
        ], true);
    }
}
