<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Trace\Sampler\BucketSettings;

#[CoversClass(BucketSettings::class)]
class BucketSettingsTest extends TestCase
{
    public function test_bucket_settings_initialization(): void
    {
        $capacity = 100;
        $rate = 10;
        $bucketSettings = new BucketSettings($capacity, $rate);

        $this->assertEquals($capacity, $bucketSettings->capacity);
        $this->assertEquals($rate, $bucketSettings->rate);
    }
}
