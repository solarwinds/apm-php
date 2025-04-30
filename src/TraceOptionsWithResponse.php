<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp;

class TraceOptionsWithResponse extends TraceOptions
{
    public TraceOptionsResponse $response;

    public function __construct(
        TraceOptions $options,
        TraceOptionsResponse $response,
    ) {
        parent::__construct($options->triggerTrace, $options->timestamp, $options->swKeys, $options->custom, $options->ignored);
        $this->response = $response;
    }

    public function __toString()
    {
        return parent::__toString() . ';' . $this->response;
    }
}
