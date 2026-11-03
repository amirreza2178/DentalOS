<?php

namespace App\Enums;

enum FollowUpAuthorization: string
{
    case Patient = 'patient';
    case Receptionist = 'receptionist';
}
