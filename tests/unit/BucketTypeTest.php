<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\BucketType;

#[CoversClass(BucketType::class)]
class BucketTypeTest extends TestCase
{
    public function test_bucket_type_values(): void
    {
        $this->assertEquals('', BucketType::DEFAULT->value);
        $this->assertEquals('TriggerRelaxed', BucketType::TRIGGER_RELAXED->value);
        $this->assertEquals('TriggerStrict', BucketType::TRIGGER_STRICT->value);
    }

    public function test_bucket_type_enum_cases(): void
    {
        $this->assertInstanceOf(BucketType::class, BucketType::DEFAULT);
        $this->assertInstanceOf(BucketType::class, BucketType::TRIGGER_RELAXED);
        $this->assertInstanceOf(BucketType::class, BucketType::TRIGGER_STRICT);
    }
}
