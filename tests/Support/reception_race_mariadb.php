<?php

use App\Actions\Booking\ApproveOnlineAppointment;
use App\Actions\Booking\RejectOnlineAppointment;
use App\Actions\Reception\CheckInAppointment;
use App\Actions\Scheduling\CancelAppointment;
use App\Actions\Scheduling\CancelAppointmentResult;
use App\Enums\CancellationReason;
use App\Exceptions\Reception\ReceptionConflictException;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Exceptions\Scheduling\OnlineBookingConflictException;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$database = $argv[1] ?? '';
$resultPath = $argv[2] ?? '';

$allowed = ['dental_os_test', 'test_dental_os'];

if (! is_string($database) || ! in_array($database, $allowed, true)) {
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

$actor = User::query()->findOrFail((int) $payload['actor_id']);
$appointment = Appointment::query()->findOrFail((int) $payload['appointment_id']);

$startAt = (float) ($payload['start_at'] ?? 0);
if (microtime(true) > $startAt) {
    fwrite(STDOUT, sprintf("late:%.3f\n", microtime(true) - $startAt));
}

while (microtime(true) < $startAt) {
    usleep(500);
}

try {
    $result = match ($payload['action']) {
        'cancel' => (function () use ($appointment, $actor, $payload): string {
            $outcome = app(CancelAppointment::class)->cancel(
                $appointment,
                $actor,
                CancellationReason::from((string) $payload['reason']),
                $payload['note'] ?? null,
            );

            return $outcome->outcome === CancelAppointmentResult::OUTCOME_CANCELLED
                ? 'ok'
                : 'already';
        })(),
        'check_in' => app(CheckInAppointment::class)->handle($appointment, $actor)['created'] ? 'ok' : 'ok:existing',
        'approve' => (function () use ($appointment, $actor): string {
            app(ApproveOnlineAppointment::class)->approve($appointment, $actor);

            return 'ok';
        })(),
        'reject' => (function () use ($appointment, $actor): string {
            app(RejectOnlineAppointment::class)->reject($appointment, $actor, 'زمان مناسب نیست');

            return 'ok';
        })(),
        default => throw new InvalidArgumentException('Unknown action'),
    };
    file_put_contents($resultPath, $result);
} catch (AuthorizationException $exception) {
    file_put_contents($resultPath, 'unauthorized');
} catch (ReceptionConflictException|InvalidAppointmentTransitionException|OnlineBookingConflictException $exception) {
    file_put_contents($resultPath, 'conflict');
} catch (Throwable $exception) {
    file_put_contents($resultPath, 'error:'.$exception::class);
}
