<?php

namespace Tests\Feature\Clinical;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dedicated InnoDB concurrency suite.
 *
 * Requires an isolated MariaDB database (never dental_os production/local app DB)
 * and env MARIADB_CONCURRENCY_TEST=1. Skips safely otherwise.
 */
class MariaDbConcurrencyGateTest extends TestCase
{
    public function test_dedicated_mariadb_concurrency_database_is_available_or_explicitly_blocked(): void
    {
        if (env('MARIADB_CONCURRENCY_TEST') !== '1') {
            $this->markTestSkipped(
                'Blocker: set MARIADB_CONCURRENCY_TEST=1 and a dedicated dental_os_test MariaDB database before running InnoDB concurrency proofs. Never point this suite at dental_os.'
            );
        }

        $connection = config('database.default');
        $database = config('database.connections.'.$connection.'.database');

        $this->assertContains($connection, ['mariadb', 'mysql']);
        $this->assertNotSame('dental_os', $database, 'Concurrency tests must not use the main dental_os database.');
        $this->assertSame('dental_os_test', $database);

        DB::select('select 1 as ok');
        $this->assertTrue(true);
    }
}
