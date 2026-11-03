<?php

namespace App\Actions\Staff;

use App\Enums\StaffType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveStaffAccount
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(UserRole $role, array $data): User
    {
        return DB::transaction(function () use ($role, $data): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'password' => $data['password'],
                'role' => $role,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->syncProfile($user, $role, $data);

            return $user->load('staffProfile');
        });
    }

    /**
     * Role is intentionally not taken from the request.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            if (filled($data['password'] ?? null)) {
                $user->password = $data['password'];
            }

            $user->save();
            $this->syncProfile($user, $user->role, $data);

            return $user->load('staffProfile');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncProfile(User $user, UserRole $role, array $data): void
    {
        if (! $role->requiresStaffProfile()) {
            return;
        }

        $user->staffProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'staff_type' => StaffType::from($role->value),
                'qualification' => $data['qualification'],
                'title' => $data['title'] ?? null,
                'professional_summary' => $data['professional_summary'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'can_take_clinical_notes' => $role === UserRole::Specialist
                    && (bool) ($data['can_take_clinical_notes'] ?? false),
            ],
        );
    }
}
