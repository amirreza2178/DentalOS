<?php

namespace Tests\Feature\Ops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BootstrapLocalOpsSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_bootstrap_refuses_non_local_environment(): void
    {
        config(['app.env' => 'production']);

        $this->artisan('dentalos:bootstrap-local-ops')
            ->expectsOutputToContain('Refused')
            ->assertFailed();
    }

    public function test_bootstrap_refuses_in_memory_sqlite_testing_database(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        // Force local env but keep unsafe sqlite target used by PHPUnit.
        config(['app.env' => 'local']);

        $this->artisan('dentalos:bootstrap-local-ops')
            ->expectsOutputToContain('Refused')
            ->assertFailed();
    }
}
