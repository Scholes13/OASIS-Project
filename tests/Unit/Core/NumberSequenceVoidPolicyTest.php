<?php

namespace Tests\Unit\Core;

use App\Models\Core\NumberSequence;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NumberSequenceVoidPolicyTest extends TestCase
{
    #[Test]
    public function it_does_not_reuse_void_numbers(): void
    {
        $sequence = $this->partialMock(NumberSequence::class, function ($mock): void {
            $mock->shouldReceive('getNextNumber')->once()->andReturn(9);
            $mock->shouldReceive('removeVoidNumber')->never();
        });

        $sequence->setAttribute('void_numbers', [8]);

        $this->assertSame(9, $sequence->getNextAvailableNumber());
    }
}
