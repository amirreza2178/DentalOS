<?php

namespace App\Console\Commands;

use App\Actions\Reception\CheckInAppointment;
use App\Actions\Scheduling\ReservationData;
use App\Actions\Scheduling\ReserveAppointment;
use App\Enums\AppointmentSource;
use App\Enums\BookingConfirmationMode;
use App\Enums\PatientIdentityStatus;
use App\Enums\PatientStatus;
use App\Enums\Qualification;
use App\Enums\StaffType;
use App\Enums\UserRole;
use App\Enums\Weekday;
use App\Models\Chair;
use App\Models\ClinicSetting;
use App\Models\Patient;
use App\Models\StaffProfile;
use App\Models\StaffSchedule;
use App\Models\TreatmentService;
use App\Models\User;
use App\Models\WorkShift;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

/**
 * Builds a disposable synthetic demo database. Never targets dental_os.
 */
class PrepareDemoEnvironmentCommand extends Command
{
    protected $signature = 'dentalos:prepare-demo
                            {--reset : Wipe and recreate ONLY the verified dental_os_demo database}
                            {--i-understand-demo-reset : Required confirmation flag when using --reset}';

    protected $description = 'Prepare isolated synthetic demo data in dental_os_demo (never dental_os)';

    private const DEMO_DATABASE = 'dental_os_demo';

    /**
     * @var list<string>
     */
    private const ALLOWED_HOSTS = ['127.0.0.1', 'localhost', '::1'];

    public function handle(
        ReserveAppointment $reserve,
        CheckInAppointment $checkIn,
    ): int {
        // Confirmation gate first — never open a DB handle for an unconfirmed reset.
        if ($this->option('reset') && ! $this->option('i-understand-demo-reset')) {
            $this->error('Refused: --reset requires --i-understand-demo-reset before any database connection.');

            return self::FAILURE;
        }

        $gate = $this->assertSafeDemoTarget();
        if ($gate !== null) {
            $this->error($gate);

            return self::FAILURE;
        }

        $created = $this->ensureDemoDatabaseExists();
        if ($created !== null) {
            $this->error($created);

            return self::FAILURE;
        }

        $bind = $this->bindDemoConnection();
        if ($bind !== null) {
            $this->error($bind);

            return self::FAILURE;
        }

        $identity = $this->verifyConnectedDatabase();
        if ($identity !== null) {
            $this->error($identity);

            return self::FAILURE;
        }

        $this->info('Verified isolated target: '.self::DEMO_DATABASE);

        if ($this->option('reset')) {
            $fresh = Artisan::call('migrate:fresh', [
                '--force' => true,
                '--database' => 'demo',
            ]);
            $this->output->write(Artisan::output());
            if ($fresh !== 0) {
                $this->error('migrate:fresh failed on dental_os_demo; aborting.');

                return self::FAILURE;
            }

            $recheck = $this->verifyConnectedDatabase();
            if ($recheck !== null) {
                $this->error($recheck);

                return self::FAILURE;
            }
        } else {
            $migrate = Artisan::call('migrate', [
                '--force' => true,
                '--database' => 'demo',
            ]);
            $this->output->write(Artisan::output());
            if ($migrate !== 0) {
                $this->error('migrate failed on dental_os_demo; aborting.');

                return self::FAILURE;
            }
        }

        $secrets = [];

        try {
            DB::connection('demo')->transaction(function () use (&$secrets, $reserve, $checkIn): void {
                $this->seedClinicIdentity();
                $fixtures = $this->seedOpsFixtures($secrets);
                $this->seedDemoScenario($fixtures, $secrets, $reserve, $checkIn);
            });
        } catch (Throwable $exception) {
            $this->error('Demo seed aborted safely: '.$exception->getMessage());

            return self::FAILURE;
        }

        $path = storage_path('app/private/demo-credentials.txt');
        $this->writeCredentials($path, $secrets);

        $this->newLine();
        $this->info('Synthetic demo environment ready on dental_os_demo.');
        $this->line('Point the app at DB_DATABASE=dental_os_demo (local only) before presenting.');
        $this->line('Demo credentials written only to storage/app/private/demo-credentials.txt (gitignored).');
        $this->warn('Do not show passwords on screen recordings. Never run this against dental_os.');

        return self::SUCCESS;
    }

    private function assertSafeDemoTarget(): ?string
    {
        $env = (string) config('app.env');
        if ($env === '' || $env === '0') {
            return 'Refused: APP_ENV is inconclusive; demo prep requires APP_ENV=local.';
        }

        if ($env !== 'local' || ! app()->environment('local')) {
            return 'Refused: dentalos:prepare-demo runs only when APP_ENV=local.';
        }

        $connection = (string) config('database.default');
        if (! in_array($connection, ['mariadb', 'mysql'], true)) {
            return 'Refused: demo prep requires a local MariaDB/MySQL connection (not SQLite / not '.$connection.').';
        }

        $config = config('database.connections.'.$connection);
        if (! is_array($config)) {
            return 'Refused: database connection configuration is missing.';
        }

        $host = strtolower((string) ($config['host'] ?? ''));
        if ($host === '' || ! in_array($host, self::ALLOWED_HOSTS, true)) {
            return 'Refused: database host must be loopback; remote hosts are blocked.';
        }

        $configured = strtolower((string) ($config['database'] ?? ''));
        foreach (['production', 'prod', 'staging', 'stage', 'live'] as $forbidden) {
            if (str_contains($configured, $forbidden)) {
                return "Refused: configured database name looks like {$forbidden}.";
            }
        }

        // Explicit hard stop: never operate when the live clinic DB name is the only ambiguous target
        // without our forced demo rebinding step. dental_os is allowed in .env because we override.
        if ($configured !== '' && $configured !== self::DEMO_DATABASE && $configured !== 'dental_os' && $configured !== 'dental_os_local') {
            return 'Refused: configured database "'.$configured.'" is outside the local allowlist for demo prep.';
        }

        return null;
    }

    private function ensureDemoDatabaseExists(): ?string
    {
        $connection = (string) config('database.default');
        $config = config('database.connections.'.$connection);
        if (! is_array($config)) {
            return 'Refused: cannot read base connection config.';
        }

        try {
            $server = array_merge($config, ['database' => null]);
            config(['database.connections.demo_server' => $server]);
            DB::purge('demo_server');
            DB::connection('demo_server')->statement(
                'CREATE DATABASE IF NOT EXISTS `'.self::DEMO_DATABASE.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
        } catch (Throwable $exception) {
            return 'Refused: could not ensure dental_os_demo exists ('.$exception->getMessage().').';
        }

        return null;
    }

    private function bindDemoConnection(): ?string
    {
        $connection = (string) config('database.default');
        $config = config('database.connections.'.$connection);
        if (! is_array($config)) {
            return 'Refused: cannot bind demo connection.';
        }

        config([
            'database.connections.demo' => array_merge($config, [
                'database' => self::DEMO_DATABASE,
            ]),
            'database.default' => 'demo',
        ]);
        DB::purge('demo');
        DB::setDefaultConnection('demo');

        return null;
    }

    private function verifyConnectedDatabase(): ?string
    {
        try {
            $row = DB::connection('demo')->selectOne('select database() as name');
            $actual = is_object($row) ? (string) ($row->name ?? '') : '';
        } catch (Throwable $exception) {
            return 'Refused: could not verify demo database ('.$exception->getMessage().').';
        }

        if ($actual !== self::DEMO_DATABASE) {
            return 'Refused: SELECT DATABASE() returned "'.$actual.'"; expected '.self::DEMO_DATABASE.'. Stopping.';
        }

        return null;
    }

    private function seedClinicIdentity(): void
    {
        ClinicSetting::putValue(ClinicSetting::TIMEZONE, 'Asia/Tehran');
        ClinicSetting::putValue(ClinicSetting::CLINIC_NAME, 'کلینیک نمایشی DentalOS');
        ClinicSetting::putValue(ClinicSetting::DOCTOR_DISPLAY_NAME, 'متخصص نمایشی');
        ClinicSetting::putValue(ClinicSetting::BOOKING_DISPLAY_NOTE, 'این محیط فقط داده مصنوعی دمو دارد.');
    }

    /**
     * @param  array<string, string>  $secrets
     * @return array{
     *     specialist: User,
     *     clinical: User,
     *     receptionist: User,
     *     chair: Chair,
     *     orthoChair: Chair,
     *     consult: TreatmentService,
     *     adjust: TreatmentService
     * }
     */
    private function seedOpsFixtures(array &$secrets): array
    {
        $chair = Chair::query()->updateOrCreate(
            ['code' => 'DEMO-U1'],
            [
                'name' => 'یونیت نمایشی ۱',
                'description' => 'یونیت مصنوعی دمو',
                'capabilities' => [],
                'is_active' => true,
                'sort_order' => 1,
            ],
        );
        $orthoChair = Chair::query()->updateOrCreate(
            ['code' => 'DEMO-U2'],
            [
                'name' => 'یونیت نمایشی ارتودنسی',
                'description' => 'یونیت مصنوعی دمو',
                'capabilities' => ['ortho'],
                'is_active' => true,
                'sort_order' => 2,
            ],
        );

        $shifts = [];
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
                    'name' => 'دمو صبح '.$weekday->label(),
                    'weekday' => $weekday,
                ],
                [
                    'starts_at' => '08:00:00',
                    // Extended afternoon window so afternoon demo seeds still have future slots.
                    'ends_at' => '18:00:00',
                    'is_active' => true,
                ],
            );
            $shift->breaks()->updateOrCreate(
                [
                    'name' => 'استراحت دمو',
                    'starts_at' => '12:00:00',
                ],
                ['ends_at' => '12:30:00'],
            );
            $shifts[] = $shift;
        }

        $specialist = $this->ensureDemoUser(
            'demo.ortho@dentalos.demo',
            'متخصص نمایشی',
            UserRole::Specialist,
            $secrets,
            [
                'staff_type' => StaffType::Specialist,
                'qualification' => Qualification::OrthodonticSpecialist,
                'title' => 'متخصص ارتودنسی (دمو)',
                'can_take_clinical_notes' => true,
            ],
        );
        $clinical = $this->ensureDemoUser(
            'demo.clinical@dentalos.demo',
            'کادر نمایشی',
            UserRole::ClinicalStaff,
            $secrets,
            [
                'staff_type' => StaffType::ClinicalStaff,
                'qualification' => Qualification::QualifiedClinicalPersonnel,
                'title' => null,
                'can_take_clinical_notes' => false,
            ],
        );
        $receptionist = $this->ensureDemoUser(
            'demo.reception@dentalos.demo',
            'پذیرش نمایشی',
            UserRole::Receptionist,
            $secrets,
        );

        foreach ([$specialist, $clinical] as $user) {
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

        $consult = TreatmentService::query()->updateOrCreate(
            ['code' => 'DEMO-CONS'],
            [
                'name' => 'مشاوره نمایشی',
                'description' => 'خدمت مصنوعی دمو — بدون ادعای درمانی واقعی.',
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

        $adjust = TreatmentService::query()->updateOrCreate(
            ['code' => 'DEMO-ADJ'],
            [
                'name' => 'تنظیم نمایشی',
                'description' => 'خدمت مصنوعی دمو برای نوبت داخلی.',
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

        return compact('specialist', 'clinical', 'receptionist', 'chair', 'orthoChair', 'consult', 'adjust');
    }

    /**
     * @param  array{
     *     specialist: User,
     *     clinical: User,
     *     receptionist: User,
     *     chair: Chair,
     *     orthoChair: Chair,
     *     consult: TreatmentService,
     *     adjust: TreatmentService
     * }  $fixtures
     * @param  array<string, string>  $secrets
     */
    private function seedDemoScenario(
        array $fixtures,
        array &$secrets,
        ReserveAppointment $reserve,
        CheckInAppointment $checkIn,
    ): void {
        [$first, $second, $third] = $this->futureDemoStarts();

        $patients = [
            $this->ensureDemoPatient('DEMO-P01', 'آناهیتا', 'نمایشی', '09120000001'),
            $this->ensureDemoPatient('DEMO-P02', 'کاوه', 'نمایشی', '09120000002'),
            $this->ensureDemoPatient('DEMO-P03', 'پریسا', 'نمایشی', '09120000003'),
            $this->ensureDemoPatient('DEMO-P04', 'نیما', 'نمایشی', '09120000004'),
        ];

        // Confirmed appointment for today (check-in / queue demo).
        $confirmed = $reserve->reserve(new ReservationData(
            patientId: $patients[0]->id,
            treatmentServiceId: $fixtures['consult']->id,
            startsAt: $first,
            specialistId: $fixtures['specialist']->id,
            clinicalStaffIds: [],
            chairId: $fixtures['chair']->id,
            source: AppointmentSource::Receptionist,
            actorUserId: $fixtures['receptionist']->id,
            patientNotes: 'نوبت مصنوعی دمو — تأییدشده',
        ));

        // Second confirmed later slot keeps board non-empty after check-in.
        $reserve->reserve(new ReservationData(
            patientId: $patients[1]->id,
            treatmentServiceId: $fixtures['adjust']->id,
            startsAt: $second,
            specialistId: $fixtures['specialist']->id,
            clinicalStaffIds: [$fixtures['clinical']->id],
            chairId: $fixtures['orthoChair']->id,
            source: AppointmentSource::Receptionist,
            actorUserId: $fixtures['receptionist']->id,
            patientNotes: 'نوبت مصنوعی دمو — برنامه روز',
        ));

        // Pending online request for approval demo.
        $reserve->reserve(new ReservationData(
            patientId: $patients[2]->id,
            treatmentServiceId: $fixtures['consult']->id,
            startsAt: $third,
            specialistId: $fixtures['specialist']->id,
            clinicalStaffIds: [],
            chairId: $fixtures['chair']->id,
            source: AppointmentSource::Online,
            actorUserId: null,
            patientNotes: 'درخواست آنلاین مصنوعی دمو',
        ));

        // Safe queue scenario: one checked-in patient waiting.
        $checkIn->handle($confirmed, $fixtures['receptionist']);

        unset($secrets);
    }

    /**
     * Three future Asia/Tehran starts inside today's demo shift, skipping lunch break.
     *
     * @return array{0: Carbon, 1: Carbon, 2: Carbon}
     */
    private function futureDemoStarts(): array
    {
        $now = Carbon::now('Asia/Tehran');
        $day = $now->copy()->startOfDay();
        $breakStart = $day->copy()->setTime(12, 0);
        $breakEnd = $day->copy()->setTime(12, 30);
        $latestStart = $day->copy()->setTime(17, 0);

        $cursor = $now->copy()->addMinutes(10)->second(0);
        $remainder = $cursor->minute % 10;
        if ($remainder !== 0) {
            $cursor->addMinutes(10 - $remainder);
        }

        $starts = [];
        while ($cursor->lte($latestStart) && count($starts) < 3) {
            if ($cursor->gte($breakStart) && $cursor->lt($breakEnd)) {
                $cursor = $breakEnd->copy();

                continue;
            }

            $starts[] = $cursor->copy();
            $cursor->addMinutes(50);
        }

        if (count($starts) < 3) {
            throw new \RuntimeException(
                'Demo seed needs 3 future slots before 17:00 Asia/Tehran. Re-run earlier in the clinic day.'
            );
        }

        return [$starts[0], $starts[1], $starts[2]];
    }

    /**
     * @param  array<string, mixed>|null  $profile
     * @param  array<string, string>  $secrets
     */
    private function ensureDemoUser(
        string $email,
        string $name,
        UserRole $role,
        array &$secrets,
        ?array $profile = null,
    ): User {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $plain = Str::password(20, symbols: false);
            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($plain),
                'role' => $role,
                'is_active' => true,
            ]);
            $secrets[$email] = $plain;
            $this->line("Created demo user {$email}");
        } else {
            $user->fill([
                'name' => $name,
                'role' => $role,
                'is_active' => true,
            ])->save();
            $this->line("Ensured demo user {$email}");
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

    private function ensureDemoPatient(string $code, string $first, string $last, string $mobile): Patient
    {
        return Patient::query()->updateOrCreate(
            ['code' => $code],
            [
                'first_name' => $first,
                'last_name' => $last,
                'mobile' => $mobile,
                'mobile_normalized' => $mobile,
                'status' => PatientStatus::Active,
                'identity_status' => PatientIdentityStatus::Verified,
                'notes_admin' => 'پرونده مصنوعی دمو — داده واقعی نیست',
            ],
        );
    }

    /**
     * @param  array<string, string>  $secrets
     */
    private function writeCredentials(string $path, array $secrets): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $lines = [
            '# DentalOS DEMO credentials — DO NOT COMMIT — synthetic only',
            'generated_at='.now()->toIso8601String(),
            'database='.self::DEMO_DATABASE,
            '',
            '# Accounts (passwords only listed when newly generated this run)',
        ];

        if ($secrets === []) {
            $lines[] = '# No new passwords generated; existing demo users were reused.';
            $lines[] = '# Emails: demo.reception@dentalos.demo , demo.ortho@dentalos.demo';
        } else {
            foreach ($secrets as $email => $password) {
                $lines[] = "email={$email}";
                $lines[] = "password={$password}";
                $lines[] = '';
            }
        }

        file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);
    }
}
