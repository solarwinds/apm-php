<?php

declare(strict_types=1);

namespace Solarwinds\ApmPhp\Tests\Unit\Trace\Sampler;

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface;
use OpenTelemetry\SDK\Trace\SpanExporter\InMemoryExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarwinds\ApmPhp\Common\Configuration\Configuration;
use Solarwinds\ApmPhp\Propagator\XTraceOptions\XTraceOptionsBaggage;
use Solarwinds\ApmPhp\Trace\Sampler\BucketSettings;
use Solarwinds\ApmPhp\Trace\Sampler\BucketType;
use Solarwinds\ApmPhp\Trace\Sampler\Flags;
use function Solarwinds\ApmPhp\Trace\Sampler\httpSpanMetadata;
use function Solarwinds\ApmPhp\Trace\Sampler\parseSettings;
use Solarwinds\ApmPhp\Trace\Sampler\Sampler;
use Solarwinds\ApmPhp\Trace\Sampler\SampleSource;
use Solarwinds\ApmPhp\Trace\Sampler\Settings;

class TestSampler extends Sampler
{
    public function __construct(?MeterProviderInterface $meterProvider, Configuration $config, mixed $settings)
    {
        parent::__construct($meterProvider, $config, $settings);
    }
}

#[CoversClass(Sampler::class)]
class SamplerTest extends TestCase
{
    public function test_handles_non_http_spans_properly(): void
    {
        $spanKind = SpanKind::KIND_SERVER;
        $attributes = Attributes::create([
            'network.transport' => 'udp',
        ]);
        $output = httpSpanMetadata($spanKind, $attributes);
        $this->assertEquals(['http' => false], $output);
    }

    public function test_handles_http_client_spans_properly(): void
    {
        $spanKind = SpanKind::KIND_CLIENT;
        $attributes = Attributes::create([
            'http.request.method' => 'GET',
            'http.response.status_code' => 200,
            'server.address' => 'solarwinds.com',
            'url.scheme' => 'https',
            'url.path' => '',
        ]);
        $output = httpSpanMetadata($spanKind, $attributes);
        $this->assertEquals(['http' => false], $output);
    }

    public function test_handles_http_server_spans_properly(): void
    {
        $spanKind = SpanKind::KIND_SERVER;
        $attributes = Attributes::create([
            'http.request.method' => 'GET',
            'http.response.status_code' => 200,
            'server.address' => 'solarwinds.com',
            'url.scheme' => 'https',
            'url.path' => '',
        ]);

        $output = httpSpanMetadata($spanKind, $attributes);
        $this->assertEquals([
            'http' => true,
            'method' => 'GET',
            'status' => 200,
            'scheme' => 'https',
            'hostname' => 'solarwinds.com',
            'path' => '',
            'url' => 'https://solarwinds.com',
        ], $output);
    }

    public function test_handles_legacy_http_server_spans_properly(): void
    {
        $spanKind = SpanKind::KIND_SERVER;
        $attributes = Attributes::create([
            'http.method' => 'GET',
            'http.status_code' => '200',
            'http.scheme' => 'https',
            'net.host.name' => 'solarwinds.com',
            'http.target' => '',
        ]);
        $output = httpSpanMetadata($spanKind, $attributes);
        $this->assertEquals([
            'http' => true,
            'method' => 'GET',
            'status' => 200,
            'scheme' => 'https',
            'hostname' => 'solarwinds.com',
            'path' => '',
            'url' => 'https://solarwinds.com',
        ], $output);
    }

    public function test_correctly_parses_json_settings(): void
    {
        $timestamp = time();
        $json = [
            'flags' => 'SAMPLE_START,SAMPLE_THROUGH_ALWAYS,TRIGGER_TRACE,OVERRIDE',
            'value' => 500_000,
            'arguments' => [
                'BucketCapacity' => 0.2,
                'BucketRate' => 0.1,
                'TriggerRelaxedBucketCapacity' => 20,
                'TriggerRelaxedBucketRate' => 10,
                'TriggerStrictBucketCapacity' => 2,
                'TriggerStrictBucketRate' => 1,
                'SignatureKey' => 'key',
            ],
            'timestamp' => $timestamp,
            'ttl' => 120,
            'warning' => 'warning',
        ];
        $pair = parseSettings($json);
        $expected = new Settings(
            500_000,
            SampleSource::Remote,
            Flags::SAMPLE_START->value | Flags::SAMPLE_THROUGH_ALWAYS->value | Flags::TRIGGERED_TRACE->value | Flags::OVERRIDE->value,
            [
                BucketType::DEFAULT->value => new BucketSettings(0.2, 0.1),
                BucketType::TRIGGER_RELAXED->value => new BucketSettings(20, 10),
                BucketType::TRIGGER_STRICT->value => new BucketSettings(2, 1),
            ],
            'key',
            $timestamp,
            120
        );
        $this->assertTrue(is_array($pair));
        $this->assertArrayHasKey('settings', $pair);
        $setting = $pair['settings'];
        $this->assertEquals($expected, $setting);
        $this->assertArrayHasKey('warning', $pair);
        $this->assertEquals('warning', $pair['warning']);
    }

    public function test_respects_enabled_settings_when_no_config_or_transaction_settings(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig(['triggerTrace' => false]),
            $this->createSettings(true, null)
        );
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();
        $tracer = $tracerProvider->getTracer('testRespectsEnabledSettingsWhenNoConfigOrTransactionSettings');
        $main = $tracer->spanBuilder('test')->startSpan();
        $mainScope = $main->activate();
        $this->assertTrue($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['SampleRate' => 1000000, 'SampleSource' => 6, 'BucketCapacity' => 10, 'BucketRate' => 1], $spans[0]->getAttributes()->toArray());
    }

    private function createConfig(array $options): Configuration
    {
        return new Configuration(
            true,
            'test',
            'localhost',
            [],
            $options['tracing'] ?? null,
            $options['triggerTrace'] ?? false,
            null,
            $options['transactionSettings'] ?? []
        );
    }

    private function createSettings(bool $enabled, ?string $signatureKey): array
    {
        return [
            'value' => 1000000,
            'flags' => $enabled ? 'SAMPLE_START,SAMPLE_THROUGH_ALWAYS,TRIGGER_TRACE' : '',
            'arguments' => [
                'BucketCapacity' => 10,
                'BucketRate' => 1,
                'TriggerRelaxedBucketCapacity' => 100,
                'TriggerRelaxedBucketRate' => 10,
                'TriggerStrictBucketCapacity' => 1,
                'TriggerStrictBucketRate' => 0.1,
                'SignatureKey' => $signatureKey,
            ],
            'timestamp' => time(),
            'ttl' => 60,
        ];
    }

    public function test_respects_disabled_settings_when_no_config_or_transaction_settings(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig(['triggerTrace' => true]),
            $this->createSettings(false, null)
        );
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();
        $tracer = $tracerProvider->getTracer('testRespectsDisabledSettingsWhenNoConfigOrTransactionSettings');
        $main = $tracer->spanBuilder('test')->startSpan();
        $mainScope = $main->activate();
        $this->assertFalse($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
    }

    public function test_respects_enabled_config_when_no_transaction_settings(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig(['tracing' => true, 'triggerTrace' => true]),
            $this->createSettings(false, null)
        );
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();
        $tracer = $tracerProvider->getTracer('testRespectsEnabledConfigWhenNoTransactionSettings');
        $main = $tracer->spanBuilder('test')->startSpan();
        $mainScope = $main->activate();
        $this->assertTrue($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['SampleRate' => 1000000, 'SampleSource' => 6, 'BucketCapacity' => 10, 'BucketRate' => 1], $spans[0]->getAttributes()->toArray());
    }

    public function test_respects_disabled_config_when_no_transaction_settings(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig(['tracing' => false, 'triggerTrace' => false]),
            $this->createSettings(true, null)
        );
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();
        $tracer = $tracerProvider->getTracer('testRespectsDisabledConfigWhenNoTransactionSettings');
        $main = $tracer->spanBuilder('test')->startSpan();
        $mainScope = $main->activate();
        $this->assertFalse($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
    }

    public function test_respects_enabled_matching_transaction_setting(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig(['tracing' => false, 'triggerTrace' => false, 'transactionSettings' => [['tracing' => true, 'matcher' => fn () => true]]]),
            $this->createSettings(false, null)
        );
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();
        $tracer = $tracerProvider->getTracer('testRespectsEnabledMatchingTransactionSetting');
        $main = $tracer->spanBuilder('test')->startSpan();
        $mainScope = $main->activate();
        $this->assertTrue($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['SampleRate' => 1000000, 'SampleSource' => 6, 'BucketCapacity' => 10, 'BucketRate' => 1], $spans[0]->getAttributes()->toArray());
    }

    public function test_respects_disabled_matching_transaction_setting(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig(['tracing' => true, 'triggerTrace' => true, 'transactionSettings' => [['tracing' => false, 'matcher' => fn () => true]]]),
            $this->createSettings(true, null)
        );
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();
        $tracer = $tracerProvider->getTracer('testRespectsDisabledMatchingTransactionSetting');
        $main = $tracer->spanBuilder('test')->startSpan();
        $mainScope = $main->activate();
        $this->assertFalse($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(0, $spans);
    }

    public function test_respects_first_matching_transaction_setting(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig(['tracing' => false, 'triggerTrace' => false, 'transactionSettings' => [['tracing' => true, 'matcher' => fn () => true], ['tracing' => false, 'matcher' => fn () => true]]]),
            $this->createSettings(false, null)
        );
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();
        $tracer = $tracerProvider->getTracer('testRespectsEnabledMatchingTransactionSetting');
        $main = $tracer->spanBuilder('test')->startSpan();
        $mainScope = $main->activate();
        $this->assertTrue($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['SampleRate' => 1000000, 'SampleSource' => 6, 'BucketCapacity' => 10, 'BucketRate' => 1], $spans[0]->getAttributes()->toArray());
    }

    public function test_matches_non_http_spans_properly(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig([
                'tracing' => false,
                'triggerTrace' => false,
                'transactionSettings' => [['tracing' => true, 'matcher' => fn (string $name) => $name === '1:test']],
            ]),
            $this->createSettings(false, null)
        );

        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();

        $tracer = $tracerProvider->getTracer('testMatchesNonHttpSpansProperly');
        $main = $tracer->spanBuilder('test')->setSpanKind(SpanKind::KIND_CLIENT)->startSpan();
        $mainScope = $main->activate();
        $this->assertTrue($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['SampleRate' => 1000000, 'SampleSource' => 6, 'BucketCapacity' => 10, 'BucketRate' => 1], $spans[0]->getAttributes()->toArray());
    }

    public function test_matches_http_spans_properly(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig([
                'tracing' => false,
                'triggerTrace' => false,
                'transactionSettings' => [['tracing' => true, 'matcher' => fn (string $name) => $name === 'http://localhost/test']],
            ]),
            $this->createSettings(false, null)
        );

        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();

        $tracer = $tracerProvider->getTracer('testMatchesHttpSpansProperly');
        $main = $tracer->spanBuilder('test')
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setAttributes([
                'http.request.method' => 'GET',
                'url.scheme' => 'http',
                'server.address' => 'localhost',
                'url.path' => '/test',
            ])
            ->startSpan();
        $mainScope = $main->activate();
        $this->assertTrue($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['SampleRate' => 1000000, 'SampleSource' => 6, 'BucketCapacity' => 10, 'BucketRate' => 1, 'http.request.method' => 'GET', 'url.scheme' => 'http', 'server.address' => 'localhost', 'url.path' => '/test'], $spans[0]->getAttributes()->toArray());
    }

    public function test_matches_deprecated_http_spans_properly(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig([
                'tracing' => false,
                'triggerTrace' => false,
                'transactionSettings' => [['tracing' => true, 'matcher' => fn (string $name) => $name === 'http://localhost/test']],
            ]),
            $this->createSettings(false, null)
        );

        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();

        $tracer = $tracerProvider->getTracer('testMatchesDeprecatedHttpSpansProperly');
        $main = $tracer->spanBuilder('test')
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setAttributes([
                'http.method' => 'GET',
                'http.scheme' => 'http',
                'net.host.name' => 'localhost',
                'http.target' => '/test',
            ])
            ->startSpan();
        $mainScope = $main->activate();
        $this->assertTrue($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['SampleRate' => 1000000, 'SampleSource' => 6, 'BucketCapacity' => 10, 'BucketRate' => 1, 'http.method' => 'GET', 'http.scheme' => 'http', 'net.host.name' => 'localhost', 'http.target' => '/test'], $spans[0]->getAttributes()->toArray());
    }

    public function test_picks_up_trigger_trace(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig(['triggerTrace' => true]),
            $this->createSettings(true, null)
        );
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();
        $baggage = XTraceOptionsBaggage::getBuilder()->set('x-trace-options', 'trigger-trace')->build();
        $context = Context::getCurrent()->withContextValue($baggage);
        $tracer = $tracerProvider->getTracer('testPicksUpTriggerTrace');
        $main = $tracer->spanBuilder('test')
            ->setParent($context)
            ->startSpan();
        $mainScope = $main->activate();
        $this->assertTrue($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['BucketCapacity' => 1, 'BucketRate' => 0.1, 'TriggeredTrace' => true], $spans[0]->getAttributes()->toArray());
        $traceState = $spans[0]->getContext()->getTraceState();
        $this->assertEquals('trigger-trace####ok', $traceState->get('xtrace_options_response'));
    }

    public function test_picks_up_trigger_trace_ignored_fields(): void
    {
        $sampler = new TestSampler(
            null,
            $this->createConfig(['triggerTrace' => true]),
            $this->createSettings(true, null)
        );
        $spanExporter = new InMemoryExporter();
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new SimpleSpanProcessor($spanExporter))
            ->setSampler($sampler)
            ->build();
        $baggage = XTraceOptionsBaggage::getBuilder()->set('x-trace-options', 'abc,bcd')->build();
        $context = Context::getCurrent()->withContextValue($baggage);
        $tracer = $tracerProvider->getTracer('testPicksUpTriggerTrace');
        $main = $tracer->spanBuilder('test')
            ->setParent($context)
            ->startSpan();
        $mainScope = $main->activate();
        $this->assertTrue($main->isRecording());
        $mainScope->detach();
        $main->end();
        $spans = $spanExporter->getSpans();
        $this->assertCount(1, $spans);
        $this->assertEquals(['SampleRate' => 1000000, 'SampleSource' => 6, 'BucketCapacity' => 10.0, 'BucketRate' => 1.0], $spans[0]->getAttributes()->toArray());
        $traceState = $spans[0]->getContext()->getTraceState();
        $this->assertEquals('trigger-trace####not-requested;ignored####abc....bcd', $traceState->get('xtrace_options_response'));
    }
}
