<?php

namespace AdminPanel\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class OverrideHandlerMiddleware
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() == 'GET' && empty($request->getHeader('X-Requested-With'))) {
            $request = $request->withAttribute('auth', false)->withAttribute('handler', [\AdminPanel\Endpoints\HomePageController::class, 'getHtmlTemplate']);
        }
        return $handler($request);
    }
}
