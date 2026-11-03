<?php

namespace App\Actions\Scheduling;

use Illuminate\Support\Carbon;

readonly class SlotOption
{
    /**
     * @param  list<int>  $specialistIds
     * @param  list<int>  $clinicalStaffIds
     * @param  list<int>  $chairIds
     */
    public function __construct(
        public Carbon $startsAt,
        public Carbon $endsAt,
        public array $specialistIds,
        public array $clinicalStaffIds,
        public array $chairIds,
    ) {}
}
