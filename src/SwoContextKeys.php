<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextKeyInterface;

final class SwoContextKeys
{
    public static function xtraceoptions(): ContextKeyInterface
    {
        static $instance;

        return $instance ??= Context::createKey('solarwinds-x-trace-options');
    }

    public static function xtraceoptionsresponse(): ContextKeyInterface
    {
        static $instance;

        return $instance ??= Context::createKey('solarwinds-x-trace-options-response');
    }
}
