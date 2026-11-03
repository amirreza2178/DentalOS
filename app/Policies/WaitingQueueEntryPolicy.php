<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\WaitingQueueEntry;

class WaitingQueueEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist, UserRole::Specialist);
    }

    public function callNext(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }

    public function remove(User $user, WaitingQueueEntry $entry): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Receptionist);
    }
}
