<?php

namespace App\Actions;

use App\Metrics\MetricsInterface;
use Prometheus\RenderTextFormat;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class MetricsAction
{
    public function __construct(private MetricsInterface $metrics)
    {
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $response->getBody()->write($this->metrics->expose());

        return $response->withAddedHeader('Content-Type', RenderTextFormat::MIME_TYPE);
    }
}