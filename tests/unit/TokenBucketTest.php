<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\TokenBucket;

#[CoversClass(TokenBucket::class)]
class TokenBucketTest extends TestCase
{
    public function test_initialization(): void
    {
        $bucket = new TokenBucket(10, 1);
        $this->assertEquals(10, $bucket->getCapacity());
        $this->assertEquals(1, $bucket->getRate());
    }

    public function test_update(): void
    {
        $bucket = new TokenBucket(10, 1);
        $bucket->update(20, 2);
        $this->assertEquals(20, $bucket->getCapacity());
        $this->assertEquals(2, $bucket->getRate());
    }

    public function test_consume(): void
    {
        $bucket = new TokenBucket(10);
        $this->assertTrue($bucket->consume(5));
        $this->assertEquals(5, $bucket->getTokens());
        $this->assertFalse($bucket->consume(6));
        $this->assertEquals(5, $bucket->getTokens());
    }

    public function test_starts_full(): void
    {
        $bucket = new TokenBucket(2, 1);
        $this->assertTrue($bucket->consume(2));
    }

    public function test_cannot_consume_more_than_it_contains(): void
    {
        $bucket = new TokenBucket(1, 1);
        $this->assertFalse($bucket->consume(2));
        $this->assertTrue($bucket->consume());
    }

    public function test_replenishes_over_time(): void
    {
        $bucket = new TokenBucket(2, 1);
        $this->assertTrue($bucket->consume(2));
        sleep(2);
        $this->assertTrue($bucket->consume(2));
    }

    public function test_does_not_replenish_more_than_its_capacity(): void
    {
        $bucket = new TokenBucket(2, 1);
        $this->assertTrue($bucket->consume(2));
        sleep(2);
        $this->assertFalse($bucket->consume(4));
    }

    public function test_can_be_updated(): void
    {
        $bucket = new TokenBucket(1, 1);
        $this->assertFalse($bucket->consume(2));
        $bucket->update(2);
        $this->assertTrue($bucket->consume(2));
    }

    public function test_decreases_tokens_to_capacity_when_updating_to_a_lower_one(): void
    {
        $bucket = new TokenBucket(2, 1);
        $bucket->update(1);
        $this->assertFalse($bucket->consume(2));
    }

    public function test_can_be_updated_while_running(): void
    {
        $bucket = new TokenBucket(8, 0);
        $this->assertTrue($bucket->consume(8));
        $bucket->update(null, 2);
        sleep(4);
        $this->assertTrue($bucket->consume(8));
    }

    public function test_defaults_to_zero(): void
    {
        $bucket = new TokenBucket();
        sleep(1);
        $this->assertFalse($bucket->consume());
    }
}
