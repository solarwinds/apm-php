<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Common\Configuration;

enum TracingMode: int
{
    /*
     * PHP doesn't support Enum value refer to enum cases
     * ALWAYS = SAMPLE_START | SAMPLE_THROUGH_ALWAYS
     */
    case ALWAYS = 0x4 | 0x10;
    case NEVER = 0x0;
}
