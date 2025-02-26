<?php

namespace App\Middlewares;

use App\Metrics\MetricsInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\ScopeInterface;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LogLevel;

class ActionMetricsMiddleware implements MiddlewareInterface
{
    private ?Counter $counter = null;
    private Gauge $gauge;
    private Histogram $histogram;
    private TracerInterface $tracer;
    private SpanInterface $execSpan;
    private ScopeInterface $scope;

    public function __construct(private MetricsInterface $metrics)
    {
        if (!ini_set('OTEL_LOG_LEVEL', LogLevel::ERROR)) {
            $_SERVER['OTEL_LOG_LEVEL'] = LogLevel::ERROR;
        }

        $transport = (new OtlpHttpTransportFactory())
            ->create('http://jaeger:4318/v1/traces', 'application/json');

        $resource = ResourceInfo::create(Attributes::create([
            'service.name' => 'observability',

            // Опционально: дополнительные атрибуты
            'service.version' => '1.0.0',
            'service.namespace' => 'dev',
            'deployment.environment' => 'dev',
            'telemetry.sdk.name' => 'opentelemetry',
            'telemetry.sdk.language' => 'php',
            'telemetry.sdk.version' => '1.0.0',
        ]));

        $exporter = new SpanExporter($transport);

        $tracerProvider = TracerProvider::builder()
            ->setResource($resource)
            ->addSpanProcessor(new SimpleSpanProcessor($exporter))
            ->build();


        $this->tracer = $tracerProvider->getTracer('monolith');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->beforeAction($request);

        $response = $handler->handle($request);

        $this->afterAction($request);

        return $response;
    }

    private function beforeAction(ServerRequestInterface $request): void
    {
        $this->execSpan = $this->tracer->spanBuilder('http_operation')
            ->setAttribute('service.name', 'observability')
            ->setAttribute('http.method', $request->getMethod())
            ->setAttribute('http.route', $request->getUri()->getPath())
            ->setAttribute('custom.tag', 'my-value')
            ->startSpan();

        $this->counter = $this->metrics->registerCounter(
            namespace: 'observability',
            name: 'http_requests_total',
            help: 'Total HTTP requests',
            labels: ['endpoint', 'method'],
        );

        $this->gauge = $this->metrics->registerGauge(
            namespace: 'observability',
            name: 'php_memory_peak_usage',
            help: 'PHP memory peak usage',
            labels: ['endpoint'],
        );

        $this->histogram = $this->metrics->registerHistogram(
            namespace: 'observability',
            name: 'php_memory_peak_usage_histogram',
            help: 'PHP memory peak usage histogram',
            labels: ['endpoint'],
            buckets: [
                10 * 1024 * 1024,    // 10 MB - очень лёгкие запросы
                25 * 1024 * 1024,    // 25 MB  - лёгкие
                50 * 1024 * 1024,    // 50 MB  - средние
                100 * 1024 * 1024,   // 100 MB - тяжёлые
                200 * 1024 * 1024,   // 200 MB - очень тяжёлые
                300 * 1024 * 1024,   // 300 MB - критичные
                500 * 1024 * 1024,   // 500 MB - экстремальные
                1024 * 1024 * 1024   // 1 GB - на всякий случай
            ]
        );

        $this->scope = $this->execSpan->activate();
    }

    private function afterAction(ServerRequestInterface $request): void
    {
        $this->counter->inc([$request->getUri()->getPath(), $request->getMethod()]);

        $this->gauge->set(memory_get_peak_usage(false), [$request->getUri()->getPath()]);

        $this->histogram->observe(memory_get_peak_usage(false), [$request->getUri()->getPath()]);

        $this->execSpan->addEvent(
            'processing_complete',
            ['items_processed' => 1, 'memory_used' => memory_get_peak_usage(false)]
        );
        $this->scope->detach();
        $this->execSpan->end();
    }
}