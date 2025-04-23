<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\TokenBucket;

#[CoversClass(TokenBucket::class)]
class TokenBucketTest extends TestCase
{
    public function testInitialization(): void
    {
        $bucket = new TokenBucket(10, 1);
        $this->assertEquals(10, $bucket->getCapacity());
        $this->assertEquals(1, $bucket->getRate());
    }

    public function testUpdate(): void
    {
        $bucket = new TokenBucket(10, 1);
        $bucket->update(20, 2);
        $this->assertEquals(20, $bucket->getCapacity());
        $this->assertEquals(2, $bucket->getRate());
    }

    public function testConsume(): void
    {
        $bucket = new TokenBucket(10);
        $this->assertTrue($bucket->consume(5));
        $this->assertEquals(5, $bucket->getTokens());
        $this->assertFalse($bucket->consume(6));
        $this->assertEquals(5, $bucket->getTokens());
    }

    public function testStartsFull(): void
    {
        $bucket = new TokenBucket(2, 1);
        $this->assertTrue($bucket->consume(2));
    }

    public function testCannotConsumeMoreThanItContains(): void
    {
        $bucket = new TokenBucket(1, 1);
        $this->assertFalse($bucket->consume(2));
        $this->assertTrue($bucket->consume());
    }

    public function testReplenishesOverTime(): void
    {
        $bucket = new TokenBucket(2, 1);
        $this->assertTrue($bucket->consume(2));
        sleep(2);
        $this->assertTrue($bucket->consume(2));
    }

    public function testDoesNotReplenishMoreThanItsCapacity(): void
    {
        $bucket = new TokenBucket(2, 1);
        $this->assertTrue($bucket->consume(2));
        sleep(2);
        $this->assertFalse($bucket->consume(4));
    }

    public function testCanBeUpdated(): void
    {
        $bucket = new TokenBucket(1, 1);
        $this->assertFalse($bucket->consume(2));
        $bucket->update(2);
        $this->assertTrue($bucket->consume(2));
    }

    public function testDecreasesTokensToCapacityWhenUpdatingToALowerOne(): void
    {
        $bucket = new TokenBucket(2, 1);
        $bucket->update(1);
        $this->assertFalse($bucket->consume(2));
    }

    public function testCanBeUpdatedWhileRunning(): void
    {
        $bucket = new TokenBucket(8, 0);
        $this->assertTrue($bucket->consume(8));
        $bucket->update(null, 2);
        sleep(4);
        $this->assertTrue($bucket->consume(8));
    }

    public function testDefaultsToZero(): void
    {
        $bucket = new TokenBucket();
        sleep(1);
        $this->assertFalse($bucket->consume());
    }
}