<?php

declare(strict_types=1);

use OpenTelemetry\SDK\Common\Util\ComposerHandler;
use Solarwinds\ApmPhp\SdkAutoloader;

if (ComposerHandler::isRunning() === false) {
    SdkAutoloader::autoload();
}
