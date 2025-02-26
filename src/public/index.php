<?php

use App\Actions\MetricsAction;
use App\Actions\TestAction;
use App\Metrics\MetricsInterface;
use App\Metrics\PrometheusRedisMetrics;
use App\Middlewares\ActionMetricsMiddleware;
use DI\Container;
use Gelf\Publisher;
use Gelf\PublisherInterface;
use Gelf\Transport\UdpTransport;
use Monolog\Handler\GelfHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();

$container->set(MetricsInterface::class, static function () {
    return new PrometheusRedisMetrics('redis', '6380');
});

$container->set(LoggerInterface::class, static function () {
    $transport = new UdpTransport('graylog', '12201');
    $publisher = new Publisher($transport);
    $handler = new GelfHandler($publisher, 'info');

    return new Logger('main', [$handler]);
});

$container->set(PublisherInterface::class, Publisher::class);
$container->set(ActionMetricsMiddleware::class, static function (Container $container) {
    return new ActionMetricsMiddleware(
        $container->get(MetricsInterface::class),
    );
});

AppFactory::setContainer($container);

$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Hello world!");
    return $response;
})->addMiddleware($app->getContainer()->get(ActionMetricsMiddleware::class));

$app->get('/metrics', MetricsAction::class)
    ->addMiddleware($app->getContainer()->get(ActionMetricsMiddleware::class));

$app->get('/test', TestAction::class)
    ->addMiddleware($app->getContainer()->get(ActionMetricsMiddleware::class));

$app->run();