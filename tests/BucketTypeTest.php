<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\BucketType;

#[CoversClass(BucketType::class)]
class BucketTypeTest extends TestCase
{
    public function testBucketTypeValues(): void
    {
        $this->assertEquals('', BucketType::DEFAULT->value);
        $this->assertEquals('TriggerRelaxed', BucketType::TRIGGER_RELAXED->value);
        $this->assertEquals('TriggerStrict', BucketType::TRIGGER_STRICT->value);
    }

    public function testBucketTypeEnumCases(): void
    {
        $this->assertInstanceOf(BucketType::class, BucketType::DEFAULT);
        $this->assertInstanceOf(BucketType::class, BucketType::TRIGGER_RELAXED);
        $this->assertInstanceOf(BucketType::class, BucketType::TRIGGER_STRICT);
    }
}
