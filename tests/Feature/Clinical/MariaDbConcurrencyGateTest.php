<?php

namespace Tests\Feature\Clinical;

use App\Actions\Scheduling\ReservationData;
use App\Actions\Scheduling\ReserveAppointment;
use App\Enums\AppointmentSource;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsClinicSchedule;
use Tests\TestCase;

/**
 * Dedicated InnoDB concurrency suite.
 *
 * Requires MARIADB_CONCURRENCY_TEST=1 and an isolated MariaDB database:
 * - dental_os_test (admin CREATE privilege), or
 * - test_dental_os (usable via MariaDB PUBLIC test_% grants)
 *
 * Never points at dental_os.
 */
class MariaDbConcurrencyGateTest extends TestCase
{
    use BuildsClinicSchedule;

    private bool $usingIsolatedMaria = false;

    private string $isolatedDatabase = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (env('MARIADB_CONCURRENCY_TEST') !== '1') {
            return;
        }

        $database = (string) env('MARIADB_CONCURRENCY_DATABASE', 'test_dental_os');
        $allowed = ['dental_os_test', 'test_dental_os'];

        if (! in_array($database, $allowed, true)) {
            return;
        }

        $mariadb = config('database.connections.mariadb');
        $username = $mariadb['username'] ?? null;

        if (! is_string($username) || $username === '') {
            return;
        }

        config([
            'database.default' => 'mariadb',
            'database.connections.mariadb.url' => null,
            'database.connections.mariadb.host' => $mariadb['host'] ?? '127.0.0.1',
            'database.connections.mariadb.port' => $mariadb['port'] ?? '3306',
            'database.connections.mariadb.database' => $database,
            'database.connections.mariadb.username' => $username,
            'database.connections.mariadb.password' => $mariadb['password'] ?? '',
        ]);

        DB::purge('mariadb');
        DB::reconnect('mariadb');

        $this->assertNotSame('dental_os', config('database.connections.mariadb.database'));

        // Rebuild only the isolated database schema.
        $this->artisan('migrate:fresh', [
            '--force' => true,
            '--database' => 'mariadb',
        ])->assertSuccessful();

        $this->usingIsolatedMaria = true;
        $this->isolatedDatabase = $database;
    }

    protected function tearDown(): void
    {
        if ($this->usingIsolatedMaria) {
            DB::disconnect('mariadb');
            DB::purge('mariadb');
        }

        parent::tearDown();
    }

    public function test_dedicated_mariadb_concurrency_database_is_available_or_explicitly_blocked(): void
    {
        if (env('MARIADB_CONCURRENCY_TEST') !== '1') {
            $this->markTestSkipped(
                'Blocker documented: set MARIADB_CONCURRENCY_TEST=1 with isolated DB dental_os_test or test_dental_os. Never use dental_os.'
            );
        }

        if (! $this->usingIsolatedMaria) {
            $this->fail('MARIADB_CONCURRENCY_TEST=1 was set but isolated MariaDB connection could not be established safely.');
        }

        $connection = config('database.default');
        $database = config('database.connections.'.$connection.'.database');

        $this->assertContains($connection, ['mariadb', 'mysql']);
        $this->assertNotSame('dental_os', $database);
        $this->assertContains($database, ['dental_os_test', 'test_dental_os']);
        DB::select('select 1 as ok');
    }

    public function test_innodb_two_processes_compete_for_one_slot_and_only_one_reserves(): void
    {
        if (env('MARIADB_CONCURRENCY_TEST') !== '1' || ! $this->usingIsolatedMaria) {
            $this->markTestSkipped('Isolated MariaDB concurrency environment is not enabled.');
        }

        Carbon::setTestNow(Carbon::parse('2026-11-07 07:00:00', 'UTC'));

        try {
            $clinic = $this->makeClinic();
            $actor = User::factory()->create([
                'role' => UserRole::Receptionist,
                'is_active' => true,
            ]);

            $resultPath = storage_path('framework/mariadb-race-result.txt');
            if (file_exists($resultPath)) {
                unlink($resultPath);
            }

            $data = new ReservationData(
                patientId: $clinic['otherPatient']->id,
                treatmentServiceId: $clinic['service']->id,
                startsAt: Carbon::parse('2026-11-07 08:00:00', 'UTC'),
                specialistId: $clinic['specialist']->id,
                clinicalStaffIds: [$clinic['assistant']->id],
                chairId: $clinic['chair']->id,
                source: AppointmentSource::Receptionist,
                actorUserId: $actor->id,
            );

            DB::beginTransaction();
            app(ReserveAppointment::class)->reserve(new ReservationData(
                patientId: $clinic['patient']->id,
                treatmentServiceId: $clinic['service']->id,
                startsAt: Carbon::parse('2026-11-07 08:00:00', 'UTC'),
                specialistId: $clinic['specialist']->id,
                clinicalStaffIds: [$clinic['assistant']->id],
                chairId: $clinic['chair']->id,
                source: AppointmentSource::Receptionist,
                actorUserId: $actor->id,
            ));

            $pipes = [];
            $process = proc_open(
                [
                    PHP_BINARY,
                    base_path('tests/Support/competing_reserve_mariadb.php'),
                    $this->isolatedDatabase,
                    $resultPath,
                    base64_encode(json_encode([
                        'patient_id' => $data->patientId,
                        'treatment_service_id' => $data->treatmentServiceId,
                        'starts_at' => '2026-11-07 08:00:00',
                        'specialist_id' => $data->specialistId,
                        'clinical_staff_ids' => $data->clinicalStaffIds,
                        'chair_id' => $data->chairId,
                        'actor_id' => $actor->id,
                        'host' => config('database.connections.mariadb.host'),
                        'port' => config('database.connections.mariadb.port'),
                        'username' => config('database.connections.mariadb.username'),
                        'password' => config('database.connections.mariadb.password'),
                    ])),
                ],
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w'],
                ],
                $pipes,
                base_path(),
            );

            $this->assertIsResource($process);
            fclose($pipes[0]);
            usleep(1500000);
            DB::commit();

            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            $result = file_exists($resultPath) ? file_get_contents($resultPath) : 'missing';
            @unlink($resultPath);

            $this->assertSame('conflict', trim((string) $result), trim(($stdout ?: '')."\n".($stderr ?: '')));
            $this->assertSame(1, Appointment::query()->where('status', 'confirmed')->count());
        } finally {
            Carbon::setTestNow();
        }
    }
}
