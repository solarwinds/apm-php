<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\BucketSettings;

#[CoversClass(BucketSettings::class)]
class BucketSettingsTest extends TestCase
{
    public function testBucketSettingsInitialization(): void
    {
        $capacity = 100;
        $rate = 10;
        $bucketSettings = new BucketSettings($capacity, $rate);

        $this->assertEquals($capacity, $bucketSettings->capacity);
        $this->assertEquals($rate, $bucketSettings->rate);
    }
}
