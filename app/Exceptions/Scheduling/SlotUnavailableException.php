<?php

namespace App\Exceptions\Scheduling;

use RuntimeException;

class SlotUnavailableException extends RuntimeException
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(public readonly array $reasons)
    {
        parent::__construct($reasons[0] ?? 'این بازه قابل رزرو نیست.');
    }
}
