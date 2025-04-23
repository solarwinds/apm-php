<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

enum SampleSource: int
{
    case LocalDefault = 2;
    case Remote = 6;
}
