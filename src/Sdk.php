<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

class Sdk
{
    public static function builder(): SdkBuilder
    {
        return new SdkBuilder();
    }
}
