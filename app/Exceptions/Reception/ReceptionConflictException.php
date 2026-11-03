<?php

namespace App\Exceptions\Reception;

use RuntimeException;

class ReceptionConflictException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
