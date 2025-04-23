<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;
class RequestHeaders
{
    public ?string $XTraceOptions = null;
    public ?string $XTraceOptionsSignature = null;

    public function __construct(?string $XTraceOptions = null, ?string $XTraceOptionsSignature = null)
    {
        $this->XTraceOptions = $XTraceOptions;
        $this->XTraceOptionsSignature = $XTraceOptionsSignature;
    }
}
