<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

use OpenTelemetry\SDK\Common\Configuration\Configuration;
use OpenTelemetry\SDK\Common\Configuration\Variables;

class SdkAutoloader
{
    public static function autoload(): bool
    {
        if (!Configuration::getBoolean(Variables::OTEL_PHP_AUTOLOAD_ENABLED)) {
            return false;
        }
        Sdk::builder()->buildAndRegisterGlobal();

        return true;
    }
}
