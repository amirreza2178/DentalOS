<?php

namespace App\Exceptions\Scheduling;

use RuntimeException;

class OnlineBookingConflictException extends RuntimeException
{
    public function __construct(string $message = 'این درخواست همین الان توسط همکار دیگری تعیین تکلیف شد.')
    {
        parent::__construct($message);
    }
}
