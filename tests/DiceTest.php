<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Dice;

#[CoversClass(Dice::class)]
class DiceTest extends TestCase
{
    private Dice $dice;
    private Dice $diceFullRate;
    private Dice $diceZeroRate;

    public function test_roll_within_range(): void
    {
        $result = $this->dice->roll();
        $this->assertContains($result, [true, false]);
    }

    public function test_roll_randomness(): void
    {
        $results = array_map(fn () => $this->dice->roll() ? 1 : 0, range(1, 1000));
        $count = array_count_values($results);
        $this->assertLessThan(100, abs(($count[true] ?? 0) - ($count[false] ?? 0)));
    }

    public function test_rate_setter_getter(): void
    {
        $this->dice->setRate(5);
        $this->assertEquals(5, $this->dice->getRate());
    }

    public function test_rate_setter_negative_value(): void
    {
        $this->dice->setRate(-1);
        $this->assertEquals(0, $this->dice->getRate());
    }

    public function test_roll_zero_rate(): void
    {
        $results = array_map(fn () => $this->diceZeroRate->roll(), range(1, 1000));
        $this->assertTrue(array_reduce($results, fn ($carry, $item) => $carry && !$item, true));
    }

    public function test_roll_full_rate(): void
    {
        $results = array_map(fn () => $this->diceFullRate->roll(), range(1, 1000));
        $this->assertTrue(array_reduce($results, fn ($carry, $item) => $carry && $item, true));
    }

    protected function setUp(): void
    {
        $this->dice = new Dice(100, 50);
        $this->diceFullRate = new Dice(100, 100);
        $this->diceZeroRate = new Dice(100, 0);
    }
}
