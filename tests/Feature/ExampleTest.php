<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_home_page_shows_public_booking_entry(): void
    {
        $this->get('/')->assertOk()->assertSee('DentalOS')->assertSee('شروع درخواست نوبت');
    }
}
