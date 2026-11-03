<?php

namespace App\Exceptions\Reception;

use App\Enums\VisitStatus;
use RuntimeException;

class InvalidVisitTransitionException extends RuntimeException
{
    public function __construct(VisitStatus $from, VisitStatus $to)
    {
        parent::__construct("گذار ویزیت از «{$from->label()}» به «{$to->label()}» مجاز نیست.");
    }
}
