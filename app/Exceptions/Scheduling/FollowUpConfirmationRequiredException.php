<?php

namespace App\Exceptions\Scheduling;

use RuntimeException;

class FollowUpConfirmationRequiredException extends RuntimeException
{
    public function __construct(string $message = 'پیگیری پیشنهادی بدون تأیید بیمار یا اقدام مجاز پذیرش به نوبت قطعی تبدیل نمی‌شود.')
    {
        parent::__construct($message);
    }
}
