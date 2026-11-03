<?php

namespace Tests\Feature\Scheduling;

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

class CompetingReservationTest extends TestCase
{
    use BuildsClinicSchedule;

    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = storage_path('framework/reservation-race.sqlite');

        if (file_exists($this->databasePath)) {
            unlink($this->databasePath);
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => $this->databasePath,
            'database.connections.sqlite.busy_timeout' => 10000,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        DB::purge('sqlite');

        if (file_exists($this->databasePath)) {
            @unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function test_two_processes_compete_for_one_slot_and_only_one_reserves(): void
    {
        $clinic = $this->makeClinic();
        $actor = User::factory()->create([
            'role' => UserRole::Receptionist,
            'is_active' => true,
        ]);
        $resultPath = storage_path('framework/reservation-race-result.txt');

        if (file_exists($resultPath)) {
            unlink($resultPath);
        }

        $data = new ReservationData(
            patientId: $clinic['otherPatient']->id,
            treatmentServiceId: $clinic['service']->id,
            startsAt: Carbon::parse('2026-11-07 08:00:00', 'Asia/Tehran'),
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
            startsAt: Carbon::parse('2026-11-07 08:00:00', 'Asia/Tehran'),
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
                base_path('tests/Support/competing_reserve.php'),
                $this->databasePath,
                $resultPath,
                base64_encode(json_encode([
                    'patient_id' => $data->patientId,
                    'treatment_service_id' => $data->treatmentServiceId,
                    'starts_at' => '2026-11-07 08:00:00',
                    'specialist_id' => $data->specialistId,
                    'clinical_staff_ids' => $data->clinicalStaffIds,
                    'chair_id' => $data->chairId,
                    'actor_id' => $actor->id,
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

        $this->assertSame('conflict', trim((string) $result), trim($stdout."\n".$stderr));
        $this->assertSame(1, Appointment::query()->where('status', 'confirmed')->count());
    }
}
