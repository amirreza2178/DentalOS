<?php

use App\Actions\Scheduling\ReservationData;
use App\Actions\Scheduling\ReserveAppointment;
use App\Enums\AppointmentSource;
use App\Exceptions\Scheduling\SlotUnavailableException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$database = $argv[1] ?? '';
$resultPath = $argv[2] ?? '';
$payload = json_decode(base64_decode($argv[3] ?? ''), true);

if (! is_string($database) || ! str_contains($database, 'reservation-race') || ! is_array($payload)) {
    fwrite(STDERR, "Missing race database payload.\n");
    exit(1);
}

putenv('APP_TIMEZONE=UTC');
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE='.$database);
putenv('DB_URL=');
$_ENV['APP_TIMEZONE'] = 'UTC';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $database;
$_ENV['DB_URL'] = '';
$_SERVER['APP_TIMEZONE'] = 'UTC';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_DATABASE'] = $database;
$_SERVER['DB_URL'] = '';

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

config([
    'app.timezone' => 'UTC',
    'database.default' => 'sqlite',
    'database.connections.sqlite.url' => null,
    'database.connections.sqlite.database' => $database,
    'database.connections.sqlite.busy_timeout' => 10000,
]);

DB::purge('sqlite');
DB::reconnect('sqlite');

try {
    app(ReserveAppointment::class)->reserve(new ReservationData(
        patientId: (int) $payload['patient_id'],
        treatmentServiceId: (int) $payload['treatment_service_id'],
        startsAt: Carbon::parse($payload['starts_at'], 'UTC'),
        specialistId: (int) $payload['specialist_id'],
        clinicalStaffIds: array_map('intval', $payload['clinical_staff_ids']),
        chairId: (int) $payload['chair_id'],
        source: AppointmentSource::Receptionist,
        actorUserId: (int) $payload['actor_id'],
    ));
    file_put_contents($resultPath, 'success');
} catch (SlotUnavailableException $exception) {
    file_put_contents($resultPath, 'conflict');
} catch (Throwable $exception) {
    file_put_contents($resultPath, 'error:'.$exception::class.':'.$exception->getMessage());
}
