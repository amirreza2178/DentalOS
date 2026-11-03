<?php

namespace App\Actions\Scheduling;

use App\Enums\AppointmentSource;
use App\Enums\FollowUpAuthorization;
use Illuminate\Support\Carbon;

readonly class ReservationData
{
    /**
     * @param  list<int>  $clinicalStaffIds
     */
    public function __construct(
        public int $patientId,
        public int $treatmentServiceId,
        public Carbon $startsAt,
        public ?int $specialistId,
        public array $clinicalStaffIds,
        public ?int $chairId,
        public AppointmentSource $source,
        public ?int $actorUserId,
        public ?int $followUpId = null,
        public ?FollowUpAuthorization $followUpAuthorization = null,
        public ?int $rescheduledFromId = null,
        public ?string $patientNotes = null,
        public ?int $ignoreAppointmentId = null,
    ) {}

    public function withLink(int $rescheduledFromId): self
    {
        return new self(
            patientId: $this->patientId,
            treatmentServiceId: $this->treatmentServiceId,
            startsAt: $this->startsAt,
            specialistId: $this->specialistId,
            clinicalStaffIds: $this->clinicalStaffIds,
            chairId: $this->chairId,
            source: $this->source,
            actorUserId: $this->actorUserId,
            followUpId: $this->followUpId,
            followUpAuthorization: $this->followUpAuthorization,
            rescheduledFromId: $rescheduledFromId,
            patientNotes: $this->patientNotes,
            ignoreAppointmentId: $rescheduledFromId,
        );
    }
}
