<?php

namespace Tests\Unit;

use App\Support\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    public function test_it_normalizes_persian_digits_and_country_prefix(): void
    {
        $normalizer = new PhoneNormalizer;

        $this->assertSame('09121234567', $normalizer->normalize('۰۹۱۲۱۲۳۴۵۶۷'));
        $this->assertSame('09121234567', $normalizer->normalize('+98 912 123 4567'));
        $this->assertSame('09121234567', $normalizer->normalize('989121234567'));
        $this->assertNull($normalizer->normalize('   '));
    }
}
