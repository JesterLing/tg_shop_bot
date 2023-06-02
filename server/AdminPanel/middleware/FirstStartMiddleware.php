<?php

namespace AdminPanel\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

use GuzzleHttp\Psr7\HttpFactory;

use AdminPanel\Model\Settings;

class FirstStartMiddleware
{
    private $factory;

    public function __construct(HttpFactory $responseFactory)
    {
        $this->factory = $responseFactory;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $fs = Settings::getOnly(['first_start']);
        if ($fs['first_start']) {
            if ($request->getUri()->getPath() == '/welcome' || $request->getAttribute('handler')[0] == 'AdminPanel\Endpoints\FirstStartController') {
                return $handler($request);
            } else {
                return $this->factory->createResponse(301)->withHeader('Location', '/welcome');
            }
        }
        return $handler($request);
    }
}
