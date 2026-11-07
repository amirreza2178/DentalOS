<?php

namespace Tests\Feature\Ops;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrepareDemoEnvironmentSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_demo_refuses_non_local_environment(): void
    {
        config(['app.env' => 'production']);

        $this->artisan('dentalos:prepare-demo')
            ->expectsOutputToContain('Refused')
            ->assertFailed();
    }

    public function test_prepare_demo_refuses_sqlite_testing_database(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));

        config(['app.env' => 'local']);

        $this->artisan('dentalos:prepare-demo')
            ->expectsOutputToContain('Refused')
            ->assertFailed();
    }

    public function test_prepare_demo_reset_requires_explicit_confirmation_flag(): void
    {
        // Runs before environment/DB gates — must never open a connection.
        $this->artisan('dentalos:prepare-demo', ['--reset' => true])
            ->expectsOutputToContain('--i-understand-demo-reset')
            ->assertFailed();
    }
}
