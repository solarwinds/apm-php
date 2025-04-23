<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

enum Flags: int
{
    case OK = 0x0;
    case INVALID = 0x1;
    case OVERRIDE = 0x2;
    case SAMPLE_START = 0x4;
    case SAMPLE_THROUGH_ALWAYS = 0x10;
    case TRIGGERED_TRACE = 0x20;
}