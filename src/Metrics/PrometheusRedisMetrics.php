<?php

namespace App\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\Redis;

readonly class PrometheusRedisMetrics implements MetricsInterface
{

    private CollectorRegistry $registry;

    public function __construct(
        private string  $host,
        private string  $port,
        private ?string $password = null,
        private string  $readTimeout = '10',
        private bool    $persistentConnections = false,
    )
    {
        $adapter = new Redis(
            [
                'host' => $this->host,
                'port' => $this->port,
                'password' => $this->password,
                'timeout' => $this->readTimeout,
                'read_timeout' => $this->readTimeout,
                'persistent_connections' => $this->persistentConnections,
            ]
        );


        $this->registry = new CollectorRegistry($adapter);
    }

    function registerCounter(string $namespace, string $name, string $help, array $labels = []): Counter
    {

        return $this->registry->getOrRegisterCounter($namespace, $name, $help, $labels);
    }

    public function registerGauge(string $namespace, string $name, string $help, array $labels = []): Gauge
    {
        return $this->registry->getOrRegisterGauge($namespace, $name, $help, $labels);
    }

    public function registerHistogram(string $namespace, string $name, string $help, array $labels = [], ?array $buckets = null): Histogram
    {
        return $this->registry->registerHistogram($namespace, $name, $help, $labels, $buckets);
    }

    /**
     * @throws \Throwable
     */
    function expose(): string
    {
        $renderer = new RenderTextFormat();

        return @$renderer->render($this->registry->getMetricFamilySamples());
    }
}