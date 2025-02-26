<?php

namespace App\Metrics;

use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;

interface MetricsInterface
{
    function registerCounter(string $namespace, string $name, string $help, array $labels = []): Counter;

    function registerGauge(string $namespace, string $name, string $help, array $labels = []): Gauge;

    function registerHistogram(string $namespace, string $name, string $help, array $labels = [], ?array $buckets = null): Histogram;

    function expose(): string;
}