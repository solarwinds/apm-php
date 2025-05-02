<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\Sampler;

enum SampleSource: int
{
    case LocalDefault = 2;
    case Remote = 6;
}
