<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Common\Configuration;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\Variables;

#[CoversClass(Variables::class)]
class VariablesTest extends TestCase
{
    public function test_constants()
    {
        $this->assertEquals('SW_APM_SERVICE_KEY', Variables::SW_APM_SERVICE_KEY);
        $this->assertEquals('SW_APM_COLLECTOR', Variables::SW_APM_COLLECTOR);
        $this->assertEquals('SW_K8S_POD_NAMESPACE', Variables::SW_K8S_POD_NAMESPACE);
        $this->assertEquals('SW_K8S_POD_UID', Variables::SW_K8S_POD_UID);
        $this->assertEquals('SW_K8S_POD_NAME', Variables::SW_K8S_POD_NAME);
    }
}
