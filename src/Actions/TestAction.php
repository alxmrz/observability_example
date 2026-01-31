<?php

namespace App\Actions;

use App\Metrics\MetricsInterface;
use OpenTelemetry\API\Trace\Span;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test', name: 'test', methods: ['GET'])]
class TestAction
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MetricsInterface $metrics
    ) {
        $this->logger->withName('graylog');
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $str = str_repeat('a', 1024 * 1024 * rand(1, 25));

        $this->logger->info(
            'Message from test action',
            ['context_field' => 'value context', 'trace_id' => Span::getCurrent()->getContext()->getTraceId()]
        );

        $counter = $this->metrics->registerCounter('test', 'check_work', 'what is help?', ['action']);

        $counter->incBy(1, ['action']);

        $response->getBody()->write('REGISTERED: ' . $counter->getName() . 'VALUE=' . $counter->getHelp());

        return $response;
    }
}