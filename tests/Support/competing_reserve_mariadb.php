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

$allowed = ['dental_os_test', 'test_dental_os'];

if (! is_string($database) || ! in_array($database, $allowed, true) || $database === 'dental_os') {
    fwrite(STDERR, "Refused: isolated MariaDB race database missing or unsafe.\n");
    exit(1);
}

$raw = stream_get_contents(STDIN);
$payload = is_string($raw) ? json_decode($raw, true) : null;

if (! is_array($payload)) {
    fwrite(STDERR, "Refused: race payload missing or invalid on STDIN.\n");
    exit(1);
}

foreach (['APP_TIMEZONE' => 'Asia/Tehran', 'DB_CONNECTION' => 'mariadb', 'DB_DATABASE' => $database, 'DB_URL' => ''] as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$config = is_array($payload['db'] ?? null) ? $payload['db'] : [];

config([
    'app.timezone' => 'Asia/Tehran',
    'database.default' => 'mariadb',
    'database.connections.mariadb.url' => null,
    'database.connections.mariadb.host' => (string) ($config['host'] ?? '127.0.0.1'),
    'database.connections.mariadb.port' => (string) ($config['port'] ?? '3306'),
    'database.connections.mariadb.database' => $database,
    'database.connections.mariadb.username' => (string) ($config['username'] ?? ''),
    'database.connections.mariadb.password' => (string) ($config['password'] ?? ''),
]);

DB::purge('mariadb');
DB::reconnect('mariadb');

if (config('database.connections.mariadb.database') === 'dental_os') {
    fwrite(STDERR, "Refused: operational database.\n");
    exit(1);
}

try {
    app(ReserveAppointment::class)->reserve(new ReservationData(
        patientId: (int) $payload['patient_id'],
        treatmentServiceId: (int) $payload['treatment_service_id'],
        startsAt: Carbon::parse($payload['starts_at'], 'Asia/Tehran'),
        specialistId: (int) $payload['specialist_id'],
        clinicalStaffIds: array_map('intval', $payload['clinical_staff_ids'] ?? []),
        chairId: (int) $payload['chair_id'],
        source: AppointmentSource::Receptionist,
        actorUserId: (int) $payload['actor_id'],
    ));
    file_put_contents($resultPath, 'success');
} catch (SlotUnavailableException $exception) {
    file_put_contents($resultPath, 'conflict');
} catch (Throwable $exception) {
    file_put_contents($resultPath, 'error:'.$exception::class);
}
