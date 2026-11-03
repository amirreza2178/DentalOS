<?php

namespace App\Exceptions\Scheduling;

use App\Enums\AppointmentStatus;
use RuntimeException;

class InvalidAppointmentTransitionException extends RuntimeException
{
    public function __construct(public readonly AppointmentStatus $from, public readonly AppointmentStatus $to)
    {
        parent::__construct('این تغییر وضعیت برای نوبت مجاز نیست.');
    }
}
