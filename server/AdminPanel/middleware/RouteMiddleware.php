<?php

namespace AdminPanel\Middleware;

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\HttpFactory;

use \Exception;

class RouteMiddleware
{
    private $dispatcher;
    private $factory;

    public function __construct(RouteCollector $routes, HttpFactory $responseFactory)
    {
        $this->dispatcher = new GroupCountBased($routes->getData());
        $this->factory = $responseFactory;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), rawurldecode($request->getUri()->getPath()));
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return $this->factory->createResponse(404);
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                return $this->factory->createResponse(405);
                break;
            case Dispatcher::FOUND:

                if (!empty($routeInfo[2])) $request = $request->withQueryParams($routeInfo[2]);

                $private = $routeInfo[1][2];
                if (isset($private)) array_pop($routeInfo[1]);

                $request = $request->withAttribute('handler', $routeInfo[1]);
                $request = $request->withAttribute('auth', $private ?? true);
                return $handler->handle($request);

                break;
        }
    }
}
