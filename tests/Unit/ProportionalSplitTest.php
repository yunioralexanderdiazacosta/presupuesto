<?php

namespace Tests\Unit;

use App\Support\ProportionalSplit;
use PHPUnit\Framework\TestCase;

class ProportionalSplitTest extends TestCase
{
    public function test_splits_in_proportion_and_sums_exactly_to_the_total(): void
    {
        $parts = ProportionalSplit::split(300, [1 => 50, 2 => 200, 3 => 100]);

        $this->assertSame([1 => 42.86, 2 => 171.43, 3 => 85.71], $parts);
        $this->assertEqualsWithDelta(300, array_sum($parts), 0.0001);
    }

    public function test_rounding_remainder_goes_to_the_largest_fractions(): void
    {
        $parts = ProportionalSplit::split(1, [1, 1, 1]);

        $this->assertEqualsWithDelta(1, array_sum($parts), 0.0001);
        $this->assertSame([0.34, 0.33, 0.33], $parts);
    }

    public function test_integer_split_for_boxes(): void
    {
        $parts = ProportionalSplit::split(10, [1 => 1, 2 => 1, 3 => 1], 0);

        $this->assertEquals(10, array_sum($parts));
    }

    public function test_zero_weights_give_zero_parts(): void
    {
        $this->assertSame([0.0, 0.0], ProportionalSplit::split(100, [0, 0]));
    }
}
