<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Trace\SpanProcessor;

use OpenTelemetry\API\Behavior\LogsMessagesTrait;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanProcessor\NoopSpanProcessor;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SemConv\TraceAttributes;

class ExcimerProfilerSpanProcessor extends NoopSpanProcessor implements SpanProcessorInterface
{
    use LogsMessagesTrait;

    private ?\ExcimerProfiler $profiler = null;
    private static ?SpanProcessorInterface $instance = null;

    public function __construct()
    {
        if (!extension_loaded( 'excimer' )) {
            $this->logWarning('Excimer extension not loaded, profiler not enabled');
            return;
        }
        $this->profiler = new \ExcimerProfiler();
        $this->profiler->setEventType(\EXCIMER_REAL);
        $this->profiler->setPeriod(0.001);
        $this->profiler->setMaxDepth(250);
    }

    public static function getInstance(): SpanProcessorInterface
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function onStart(ReadWriteSpanInterface $span, ContextInterface $parentContext): void
    {
        if ($this->profiler !== null) {
            $this->profiler->start();
        }
    }

    public function onEnd(ReadableSpanInterface $span): void
    {
        if ($this->profiler !== null) {
            $this->profiler->stop();
            $data = $this->profiler->getLog()->getSpeedscopeData();
            $data['profiles'][0]['name'] = 'jerry-test-profile';
            file_put_contents('/tmp/speedscope.json', json_encode($data, FILE_APPEND | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ));
        }
    }

}