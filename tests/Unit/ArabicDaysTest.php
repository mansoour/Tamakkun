<?php

namespace Tests\Unit;

use App\Support\ArabicDays;
use PHPUnit\Framework\TestCase;

class ArabicDaysTest extends TestCase
{
    public function test_day_counts_use_correct_arabic_forms(): void
    {
        $this->assertSame('اليوم', ArabicDays::until(0));
        $this->assertSame('غدًا', ArabicDays::until(1));
        $this->assertSame('بعد يومين', ArabicDays::until(2));
        $this->assertSame('بعد 5 أيام', ArabicDays::until(5));
        $this->assertSame('بعد 18 يومًا', ArabicDays::until(18));
        $this->assertSame('مضى الموعد', ArabicDays::until(-1));
    }

    public function test_point_counts_use_correct_arabic_forms(): void
    {
        $this->assertSame('درجة واحدة', ArabicDays::points(1));
        $this->assertSame('درجتين', ArabicDays::points(-2));
        $this->assertSame('3 درجات', ArabicDays::points(3));
        $this->assertSame('15 درجة', ArabicDays::points(15));
    }
}
