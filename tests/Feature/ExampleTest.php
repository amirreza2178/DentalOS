<?php

namespace Tests\Feature;

use App\Support\ClinicIdentity;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_home_page_shows_public_booking_entry(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(ClinicIdentity::DEFAULT_CLINIC_DISPLAY_NAME, false)
            ->assertSee('شروع درخواست نوبت', false);
    }
}
