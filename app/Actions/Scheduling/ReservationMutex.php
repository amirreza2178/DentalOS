<?php

namespace App\Actions\Scheduling;

use App\Models\Chair;
use App\Models\Patient;
use App\Models\ReservationLock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Serializes reservations for one resource.
 *
 * MariaDB keeps the competing transaction on SELECT ... FOR UPDATE.
 * SQLite drops FOR UPDATE, so the same row is also write-locked by
 * incrementing reservation_locks.lock_version inside the transaction.
 * Callers always acquire locks in (resource_type, resource_id) order.
 */
class ReservationMutex
{
    public const PATIENT = 'patient';

    /**
     * @param  list<array{type: string, id: int}>  $resources
     */
    public function acquire(array $resources): void
    {
        $resources = $this->sorted($resources);

        foreach ($resources as $resource) {
            $this->writeLock($resource['type'], $resource['id']);
            ReservationLock::query()
                ->where('resource_type', $resource['type'])
                ->where('resource_id', $resource['id'])
                ->lockForUpdate()
                ->first();
        }

        $this->lockDomainRows($resources);
    }

    /**
     * @param  list<array{type: string, id: int}>  $resources
     * @return list<array{type: string, id: int}>
     */
    private function sorted(array $resources): array
    {
        $unique = [];

        foreach ($resources as $resource) {
            $unique[$resource['type'].':'.$resource['id']] = [
                'type' => $resource['type'],
                'id' => $resource['id'],
            ];
        }

        $resources = array_values($unique);
        usort($resources, function (array $left, array $right): int {
            return [$left['type'], $left['id']] <=> [$right['type'], $right['id']];
        });

        return $resources;
    }

    private function writeLock(string $type, int $id): void
    {
        $now = now()->format('Y-m-d H:i:s');

        if (DB::getDriverName() === 'sqlite') {
            DB::statement(
                'insert into reservation_locks (resource_type, resource_id, lock_version, created_at, updated_at) values (?, ?, 0, ?, ?)
                 on conflict(resource_type, resource_id) do update set lock_version = reservation_locks.lock_version + 1, updated_at = excluded.updated_at',
                [$type, $id, $now, $now],
            );

            return;
        }

        DB::statement(
            'insert into reservation_locks (resource_type, resource_id, lock_version, created_at, updated_at) values (?, ?, 0, ?, ?)
             on duplicate key update lock_version = lock_version + 1, updated_at = ?',
            [$type, $id, $now, $now, $now],
        );
    }

    /**
     * @param  list<array{type: string, id: int}>  $resources
     */
    private function lockDomainRows(array $resources): void
    {
        $chairIds = [];
        $userIds = [];
        $patientId = null;

        foreach ($resources as $resource) {
            if ($resource['type'] === 'chair') {
                $chairIds[] = $resource['id'];
            } elseif ($resource['type'] === self::PATIENT) {
                $patientId = $resource['id'];
            } else {
                $userIds[] = $resource['id'];
            }
        }

        if ($chairIds !== []) {
            Chair::query()->whereIn('id', $chairIds)->orderBy('id')->lockForUpdate()->get();
        }

        if ($userIds !== []) {
            User::query()->whereIn('id', $userIds)->orderBy('id')->lockForUpdate()->get();
        }

        if ($patientId !== null) {
            Patient::query()->whereKey($patientId)->lockForUpdate()->first();
        }
    }
}
