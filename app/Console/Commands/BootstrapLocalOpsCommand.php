<?php

namespace App\Console\Commands;

use App\Enums\BookingConfirmationMode;
use App\Enums\Qualification;
use App\Enums\StaffType;
use App\Enums\UserRole;
use App\Enums\Weekday;
use App\Models\Chair;
use App\Models\ClinicSetting;
use App\Models\StaffProfile;
use App\Models\StaffSchedule;
use App\Models\TreatmentService;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

/**
 * Local-only operational fixtures so booking and reception can be exercised end-to-end.
 * Does not invent medical credentials or production marketing claims.
 */
class BootstrapLocalOpsCommand extends Command
{
    protected $signature = 'dentalos:bootstrap-local-ops
                            {--force : Recreate schedule links for local fixture users even if already present}';

    protected $description = 'Bootstrap safe local chairs, shifts, bookable services, and ops accounts (local env only)';

    /**
     * @var list<string>
     */
    private const ALLOWED_DATABASES = ['dental_os', 'dental_os_local'];

    /**
     * @var list<string>
     */
    private const ALLOWED_HOSTS = ['127.0.0.1', 'localhost', '::1'];

    public function handle(): int
    {
        $gate = $this->assertSafeLocalTarget();
        if ($gate !== null) {
            $this->error($gate);

            return self::FAILURE;
        }

        $connection = (string) config('database.default');
        $database = (string) config('database.connections.'.$connection.'.database');
        $this->info("Target database verified: {$database} (connection={$connection})");

        $credentialPath = storage_path('app/private/local-ops-credentials.txt');
        $generatedSecrets = [];

        try {
            DB::transaction(function () use (&$generatedSecrets): void {
                $this->ensureClinicTimezone();
                $chairs = $this->ensureChairs();
                $shifts = $this->ensureShifts();
                $users = $this->ensureUsers($generatedSecrets);
                $this->ensureSchedules($users, $shifts);
                $this->ensureServices($chairs);
            });
        } catch (Throwable $exception) {
            $this->error('Bootstrap aborted safely: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->writeCredentials($credentialPath, $generatedSecrets);

        $this->newLine();
        $this->info('Local operational fixtures are ready.');
        $this->line('Bookable services, chairs, weekday morning shifts, specialist, two receptionists, and clinical staff were ensured.');
        $this->line('Clinic timezone setting aligned to Asia/Tehran (approved operational timezone).');
        $this->line('Generated account passwords (if any) were written only to storage/app/private/local-ops-credentials.txt (gitignored).');
        $this->warn('Do not commit credential files. Rotate passwords before any non-local use.');

        return self::SUCCESS;
    }

    private function assertSafeLocalTarget(): ?string
    {
        $env = (string) config('app.env');
        if ($env === '' || $env === '0') {
            return 'Refused: APP_ENV is inconclusive; bootstrap requires an explicit local environment.';
        }

        if ($env !== 'local') {
            return 'Refused: dentalos:bootstrap-local-ops runs only when APP_ENV=local.';
        }

        if (! app()->environment('local')) {
            return 'Refused: application environment check did not confirm local.';
        }

        $connection = (string) config('database.default');
        if ($connection === '') {
            return 'Refused: database connection name is inconclusive.';
        }

        $config = config('database.connections.'.$connection);
        if (! is_array($config)) {
            return 'Refused: database connection configuration is missing or inconclusive.';
        }

        if ($connection === 'sqlite') {
            $database = (string) ($config['database'] ?? '');
            if ($database === '' || str_contains($database, ':memory:')) {
                return 'Refused: will not bootstrap against an empty or in-memory SQLite database.';
            }

            return 'Refused: bootstrap-local-ops targets the local MariaDB operational database only (not SQLite).';
        }

        if (! in_array($connection, ['mariadb', 'mysql'], true)) {
            return 'Refused: unsupported database driver for local ops bootstrap.';
        }

        $host = strtolower((string) ($config['host'] ?? ''));
        if ($host === '' || ! in_array($host, self::ALLOWED_HOSTS, true)) {
            return 'Refused: database host must be a local loopback address; remote/production hosts are blocked.';
        }

        $database = (string) ($config['database'] ?? '');
        if ($database === '') {
            return 'Refused: target database name is empty or inconclusive.';
        }

        $lower = strtolower($database);
        foreach (['production', 'prod', 'staging', 'stage', 'live'] as $forbidden) {
            if (str_contains($lower, $forbidden)) {
                return "Refused: database name looks like {$forbidden}; bootstrap never targets production or staging.";
            }
        }

        if (! in_array($database, self::ALLOWED_DATABASES, true)) {
            return 'Refused: database "'.$database.'" is not in the local allowlist (dental_os, dental_os_local).';
        }

        try {
            $row = DB::connection($connection)->selectOne('select database() as name');
            $actual = is_object($row) ? (string) ($row->name ?? '') : '';
        } catch (Throwable $exception) {
            return 'Refused: could not verify target database ('.$exception->getMessage().').';
        }

        if ($actual === '' || $actual !== $database) {
            return 'Refused: SELECT DATABASE() did not match the configured local target (inconclusive safety check).';
        }

        return null;
    }

    private function ensureClinicTimezone(): void
    {
        ClinicSetting::putValue(ClinicSetting::TIMEZONE, 'Asia/Tehran');
    }

    /**
     * @return array{primary: Chair, ortho: Chair}
     */
    private function ensureChairs(): array
    {
        $primary = Chair::query()->updateOrCreate(
            ['code' => 'U1'],
            [
                'name' => 'یونیت ۱',
                'description' => 'یونیت عمومی کلینیک',
                'capabilities' => [],
                'is_active' => true,
                'sort_order' => 1,
            ],
        );

        $ortho = Chair::query()->updateOrCreate(
            ['code' => 'U2'],
            [
                'name' => 'یونیت ارتودنسی',
                'description' => 'یونیت با قابلیت ارتودنسی',
                'capabilities' => ['ortho'],
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        return ['primary' => $primary, 'ortho' => $ortho];
    }

    /**
     * @return list<WorkShift>
     */
    private function ensureShifts(): array
    {
        $shifts = [];

        // Iranian clinic week: Saturday–Thursday mornings. Friday closed by omission.
        foreach ([
            Weekday::Saturday,
            Weekday::Sunday,
            Weekday::Monday,
            Weekday::Tuesday,
            Weekday::Wednesday,
            Weekday::Thursday,
        ] as $weekday) {
            $shift = WorkShift::query()->updateOrCreate(
                [
                    'name' => 'صبح '.$weekday->label(),
                    'weekday' => $weekday,
                ],
                [
                    'starts_at' => '08:00:00',
                    'ends_at' => '14:00:00',
                    'is_active' => true,
                ],
            );

            $shift->breaks()->updateOrCreate(
                [
                    'name' => 'استراحت ظهر',
                    'starts_at' => '12:00:00',
                ],
                [
                    'ends_at' => '12:30:00',
                ],
            );

            $shifts[] = $shift;
        }

        return $shifts;
    }

    /**
     * @param  array<string, string>  $generatedSecrets
     * @return array{specialist: User, clinical: User, reception1: User, reception2: User}
     */
    private function ensureUsers(array &$generatedSecrets): array
    {
        $specialist = $this->ensureUser(
            email: 'specialist@dentalos.local',
            name: 'متخصص کلینیک',
            role: UserRole::Specialist,
            generatedSecrets: $generatedSecrets,
            profile: [
                'staff_type' => StaffType::Specialist,
                'qualification' => Qualification::OrthodonticSpecialist,
                'title' => 'متخصص ارتودنسی',
                'can_take_clinical_notes' => true,
            ],
        );

        $clinical = $this->ensureUser(
            email: 'clinical@dentalos.local',
            name: 'کادر بالینی',
            role: UserRole::ClinicalStaff,
            generatedSecrets: $generatedSecrets,
            profile: [
                'staff_type' => StaffType::ClinicalStaff,
                'qualification' => Qualification::QualifiedClinicalPersonnel,
                'title' => null,
                'can_take_clinical_notes' => false,
            ],
        );

        $reception1 = $this->ensureUser(
            email: 'reception1@dentalos.local',
            name: 'پذیرش یک',
            role: UserRole::Receptionist,
            generatedSecrets: $generatedSecrets,
        );

        $reception2 = $this->ensureUser(
            email: 'reception2@dentalos.local',
            name: 'پذیرش دو',
            role: UserRole::Receptionist,
            generatedSecrets: $generatedSecrets,
        );

        return compact('specialist', 'clinical', 'reception1', 'reception2');
    }

    /**
     * @param  array<string, mixed>|null  $profile
     * @param  array<string, string>  $generatedSecrets
     */
    private function ensureUser(
        string $email,
        string $name,
        UserRole $role,
        array &$generatedSecrets,
        ?array $profile = null,
    ): User {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $plain = Str::password(24, symbols: true);
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($plain),
                'role' => $role,
                'is_active' => true,
            ]);
            $generatedSecrets[$email] = $plain;
            $this->line("Created user {$email}");
        } else {
            // Never overwrite existing passwords or delete user rows.
            $user->fill([
                'name' => $name,
                'role' => $role,
                'is_active' => true,
            ])->save();
            $this->line("Ensured user {$email}");
        }

        if ($profile !== null) {
            StaffProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'staff_type' => $profile['staff_type'],
                    'qualification' => $profile['qualification'],
                    'title' => $profile['title'],
                    'is_active' => true,
                    'can_take_clinical_notes' => $profile['can_take_clinical_notes'],
                ],
            );
        }

        return $user->fresh();
    }

    /**
     * @param  array{specialist: User, clinical: User, reception1: User, reception2: User}  $users
     * @param  list<WorkShift>  $shifts
     */
    private function ensureSchedules(array $users, array $shifts): void
    {
        foreach ([$users['specialist'], $users['clinical']] as $user) {
            foreach ($shifts as $shift) {
                StaffSchedule::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'work_shift_id' => $shift->id,
                        'schedule_date' => null,
                    ],
                    [
                        'starts_at' => null,
                        'ends_at' => null,
                        'is_unavailable' => false,
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @param  array{primary: Chair, ortho: Chair}  $chairs
     */
    private function ensureServices(array $chairs): void
    {
        TreatmentService::query()->updateOrCreate(
            ['code' => 'CONS'],
            [
                'name' => 'مشاوره ارتودنسی',
                'description' => 'جلسه مشاوره اولیه برای بررسی امکان درمان. قطعی‌شدن نوبت پس از تأیید پذیرش است.',
                'default_duration_minutes' => 30,
                'buffer_minutes' => 10,
                'requires_specialist' => true,
                'required_clinical_staff_count' => 0,
                'requires_chair' => true,
                'required_chair_capabilities' => [],
                'booking_confirmation_mode' => BookingConfirmationMode::ReceptionistApproval,
                'is_bookable_online' => true,
                'base_price' => null,
                'is_active' => true,
            ],
        );

        TreatmentService::query()->updateOrCreate(
            ['code' => 'ADJ'],
            [
                'name' => 'تنظیم ارتودنسی',
                'description' => 'جلسه تنظیم برای بیماران دارای درمان فعال. نیاز به تأیید پذیرش دارد.',
                'default_duration_minutes' => 40,
                'buffer_minutes' => 5,
                'requires_specialist' => true,
                'required_clinical_staff_count' => 1,
                'requires_chair' => true,
                'required_chair_capabilities' => ['ortho'],
                'booking_confirmation_mode' => BookingConfirmationMode::ReceptionistApproval,
                'is_bookable_online' => true,
                'base_price' => null,
                'is_active' => true,
            ],
        );

        TreatmentService::query()->updateOrCreate(
            ['code' => 'INT'],
            [
                'name' => 'ویزیت داخلی پذیرش',
                'description' => 'خدمت داخلی؛ در فهرست رزرو آنلاین نمایش داده نمی‌شود.',
                'default_duration_minutes' => 20,
                'buffer_minutes' => 5,
                'requires_specialist' => true,
                'required_clinical_staff_count' => 0,
                'requires_chair' => true,
                'required_chair_capabilities' => [],
                'booking_confirmation_mode' => BookingConfirmationMode::ReceptionistApproval,
                'is_bookable_online' => false,
                'base_price' => null,
                'is_active' => true,
            ],
        );

        unset($chairs);
    }

    /**
     * @param  array<string, string>  $generatedSecrets
     */
    private function writeCredentials(string $path, array $generatedSecrets): void
    {
        if ($generatedSecrets === []) {
            return;
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $lines = [
            '# DentalOS local ops credentials — DO NOT COMMIT',
            'generated_at='.now()->toIso8601String(),
        ];

        foreach ($generatedSecrets as $email => $password) {
            $lines[] = "email={$email}";
            $lines[] = "password={$password}";
            $lines[] = '';
        }

        file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);
    }
}
