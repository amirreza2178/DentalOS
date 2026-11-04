<?php

namespace Tests\Feature\Reception;

use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Enums\OperationalEventType;
use App\Enums\QueueStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\OperationalEvent;
use App\Models\Patient;
use App\Models\User;
use App\Models\WaitingQueueEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

/**
 * Two real PHP processes acting as two receptionists against InnoDB.
 *
 * Requires MARIADB_CONCURRENCY_TEST=1 and an isolated database
 * (test_dental_os or dental_os_test). Never points at dental_os.
 */
class ReceptionConcurrencyMariaDbTest extends TestCase
{
    use BuildsClinicSchedule;

    private static bool $schemaReady = false;

    private string $isolatedDatabase = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (env('MARIADB_CONCURRENCY_TEST') !== '1') {
            $this->markTestSkipped('Isolated MariaDB concurrency environment is not enabled (MARIADB_CONCURRENCY_TEST=1).');
        }

        $database = (string) env('MARIADB_CONCURRENCY_DATABASE', 'test_dental_os');

        if (! in_array($database, ['dental_os_test', 'test_dental_os'], true)) {
            $this->fail('MARIADB_CONCURRENCY_DATABASE must be dental_os_test or test_dental_os.');
        }

        $mariadb = config('database.connections.mariadb');

        config([
            'database.default' => 'mariadb',
            'database.connections.mariadb.url' => null,
            'database.connections.mariadb.database' => $database,
        ]);

        DB::purge('mariadb');
        DB::reconnect('mariadb');

        $this->assertNotSame('dental_os', DB::connection()->getDatabaseName());
        $this->isolatedDatabase = $database;

        if (! self::$schemaReady) {
            $this->artisan('migrate:fresh', ['--force' => true, '--database' => 'mariadb'])->assertSuccessful();
            self::$schemaReady = true;
        } else {
            $this->truncateAll();
        }

        Carbon::setTestNow(Carbon::parse('2026-11-07 07:00:00', 'Asia/Tehran'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::disconnect('mariadb');
        DB::purge('mariadb');

        parent::tearDown();
    }

    public function test_two_receptionists_cancel_the_same_appointment_at_once(): void
    {
        [$appointment, $first, $second] = $this->confirmedAppointmentWithTwoReceptionists();

        $results = $this->race([
            ['action' => 'cancel', 'appointment_id' => $appointment->id, 'actor_id' => $first->id, 'reason' => 'patient_request', 'note' => 'تماس همراه'],
            ['action' => 'cancel', 'appointment_id' => $appointment->id, 'actor_id' => $second->id, 'reason' => 'booked_in_error'],
        ]);

        sort($results);
        $this->assertSame(['already', 'ok'], $results, 'Exactly one cancel wins; the other is told it was already cancelled.');

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertContains($appointment->decision_reason, ['cancel:patient_request|تماس همراه', 'cancel:booked_in_error']);
        $this->assertSame(1, $this->eventCount(OperationalEventType::AppointmentCancelled, $appointment));
    }

    public function test_cancel_racing_check_in_never_leaves_a_cancelled_patient_in_the_queue(): void
    {
        [$appointment, $first, $second] = $this->confirmedAppointmentWithTwoReceptionists();

        $results = $this->race([
            ['action' => 'check_in', 'appointment_id' => $appointment->id, 'actor_id' => $first->id],
            ['action' => 'cancel', 'appointment_id' => $appointment->id, 'actor_id' => $second->id, 'reason' => 'patient_request'],
        ]);

        sort($results);
        $this->assertSame(['conflict', 'ok'], $results, 'Exactly one of check-in or cancel wins.');
        $this->assertQueueMatchesAppointment($appointment->refresh());
    }

    public function test_stale_cancel_after_a_colleague_checked_the_patient_in_is_refused(): void
    {
        [$appointment, $first, $second] = $this->confirmedAppointmentWithTwoReceptionists();

        $this->assertSame(['ok'], $this->race([
            ['action' => 'check_in', 'appointment_id' => $appointment->id, 'actor_id' => $first->id],
        ]));

        $this->assertSame(['conflict'], $this->race([
            ['action' => 'cancel', 'appointment_id' => $appointment->id, 'actor_id' => $second->id, 'reason' => 'patient_request'],
        ]));

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::CheckedIn, $appointment->status);
        $this->assertSame(0, $this->eventCount(OperationalEventType::AppointmentCancelled, $appointment));
        $this->assertQueueMatchesAppointment($appointment);
    }

    private function assertQueueMatchesAppointment(Appointment $appointment): void
    {
        $activeQueue = WaitingQueueEntry::query()
            ->whereHas('visit', fn ($visit) => $visit->where('appointment_id', $appointment->id))
            ->whereIn('status', QueueStatus::activeValues())
            ->count();

        if ($appointment->status === AppointmentStatus::Cancelled) {
            $this->assertSame(0, $activeQueue, 'A cancelled appointment still has an active queue entry.');
        } else {
            $this->assertSame(AppointmentStatus::CheckedIn, $appointment->status);
            $this->assertSame(1, $activeQueue);
        }
    }

    public function test_simultaneous_check_ins_of_different_patients_all_succeed_with_unique_positions(): void
    {
        $clinic = $this->makeClinic();
        $first = $this->receptionist();
        $second = $this->receptionist();
        $rounds = 4;
        $all = [];

        for ($round = 0; $round < $rounds; $round++) {
            $a = $this->confirmedAppointmentFor($clinic, $this->clinicPatient('الف'.$round, 'هم‌زمان', 'P-A'.$round), '08:'.str_pad((string) ($round * 5), 2, '0', STR_PAD_LEFT));
            $b = $this->confirmedAppointmentFor($clinic, $this->clinicPatient('ب'.$round, 'هم‌زمان', 'P-B'.$round), '09:'.str_pad((string) ($round * 5), 2, '0', STR_PAD_LEFT));

            $all = array_merge($all, $this->race([
                ['action' => 'check_in', 'appointment_id' => $a->id, 'actor_id' => $first->id],
                ['action' => 'check_in', 'appointment_id' => $b->id, 'actor_id' => $second->id],
            ]));
        }

        $this->assertSame(array_fill(0, $rounds * 2, 'ok'), $all, implode("\n", $all));

        $positions = WaitingQueueEntry::query()->orderBy('position')->pluck('position')->all();
        $this->assertSame(range(1, $rounds * 2), $positions);
    }

    public function test_approve_racing_reject_decides_the_online_request_once(): void
    {
        $clinic = $this->makeClinic();
        $first = $this->receptionist();
        $second = $this->receptionist();
        $appointment = $this->confirmedAppointmentFor($clinic, $clinic['patient'], '08:00', [
            'status' => AppointmentStatus::PendingApproval,
            'source' => AppointmentSource::Online,
        ]);

        $results = $this->race([
            ['action' => 'approve', 'appointment_id' => $appointment->id, 'actor_id' => $first->id],
            ['action' => 'reject', 'appointment_id' => $appointment->id, 'actor_id' => $second->id],
        ]);

        sort($results);
        $this->assertSame(['conflict', 'ok'], $results);

        $appointment->refresh();
        $decisions = OperationalEvent::query()
            ->whereIn('event_type', [OperationalEventType::OnlineBookingApproved->value, OperationalEventType::OnlineBookingRejected->value])
            ->where('subject_id', $appointment->id)
            ->count();

        $this->assertContains($appointment->status, [AppointmentStatus::Confirmed, AppointmentStatus::Cancelled]);
        $this->assertSame(1, $decisions);
    }

    /**
     * @return array{0: Appointment, 1: User, 2: User}
     */
    private function confirmedAppointmentWithTwoReceptionists(): array
    {
        $clinic = $this->makeClinic();

        return [
            $this->confirmedAppointmentFor($clinic, $clinic['patient'], '08:00'),
            $this->receptionist(),
            $this->receptionist(),
        ];
    }

    /**
     * @param  array<string, mixed>  $clinic
     * @param  array<string, mixed>  $overrides
     */
    private function confirmedAppointmentFor(array $clinic, Patient $patient, string $time, array $overrides = []): Appointment
    {
        $start = Carbon::parse('2026-11-07 '.$time.':00', 'Asia/Tehran');

        return Appointment::query()->create(array_merge([
            'patient_id' => $patient->id,
            'treatment_service_id' => $clinic['service']->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(40),
            'status' => AppointmentStatus::Confirmed,
            'source' => AppointmentSource::Receptionist,
        ], $overrides));
    }

    private function receptionist(): User
    {
        return User::factory()->create(['role' => UserRole::Receptionist, 'is_active' => true]);
    }

    private function eventCount(OperationalEventType $type, Appointment $appointment): int
    {
        return OperationalEvent::query()
            ->where('event_type', $type->value)
            ->where('subject_type', $appointment->getMorphClass())
            ->where('subject_id', $appointment->id)
            ->count();
    }

    /**
     * Start every payload in its own PHP process, released at the same instant.
     *
     * @param  list<array<string, mixed>>  $payloads
     * @return list<string>
     */
    private function race(array $payloads): array
    {
        $startAt = microtime(true) + 8.0;
        $connection = config('database.connections.mariadb');
        $runs = [];

        foreach ($payloads as $index => $payload) {
            $resultPath = storage_path('framework/reception-race-'.getmypid().'-'.$index.'.txt');
            @unlink($resultPath);

            $stdinPayload = json_encode([
                'action' => $payload['action'],
                'appointment_id' => $payload['appointment_id'],
                'actor_id' => $payload['actor_id'],
                'reason' => $payload['reason'] ?? null,
                'note' => $payload['note'] ?? null,
                'start_at' => $startAt + (float) ($payload['delay'] ?? 0),
                'db' => [
                    'host' => $connection['host'],
                    'port' => $connection['port'],
                    'username' => $connection['username'],
                    'password' => $connection['password'],
                ],
            ], JSON_THROW_ON_ERROR);

            $pipes = [];
            $process = proc_open(
                [PHP_BINARY, base_path('tests/Support/reception_race_mariadb.php'), $this->isolatedDatabase, $resultPath],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                base_path(),
            );

            $this->assertIsResource($process);

            try {
                fwrite($pipes[0], $stdinPayload);
            } finally {
                fclose($pipes[0]);
            }

            $runs[] = [$process, $pipes, $resultPath];
        }

        $results = [];

        foreach ($runs as [$process, $pipes, $resultPath]) {
            $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            $results[] = file_exists($resultPath) ? trim((string) file_get_contents($resultPath)) : 'missing:'.trim($output);
            @unlink($resultPath);

            $this->assertStringNotContainsString('late:', $output, 'A racer booted after the release instant, so the processes did not overlap.');
        }

        return $results;
    }

    private function truncateAll(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (Schema::getTableListing(schema: $this->isolatedDatabase, schemaQualified: false) as $table) {
            if ($table !== 'migrations') {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
