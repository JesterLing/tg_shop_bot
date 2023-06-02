<?php

namespace AdminPanel\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

use GuzzleHttp\Psr7\HttpFactory;

use \InvalidArgumentException;
use \Exception;

class RequestHandlerMiddleware implements MiddlewareInterface
{
    private $attribute_name = 'handler';
    private $factory;

    public function __construct(HttpFactory $responseFactory)
    {
        $this->factory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        [$class, $method] = $request->getAttribute($this->attribute_name);

        if (!class_exists($class)) return $next($request);

        try {
            if ($method) {
                $obj = new $class();
                return $obj->$method($request, $this->factory->createResponse());
            } else {
                $obj = new $class();

                if (method_exists($obj, '__invoke')) {
                    return $obj($request, $this->factory->createResponse());
                } else {
                    return new $class($request, $this->factory->createResponse());
                }
            }
        } catch (InvalidArgumentException $e) {
            return $this->factory->createResponse(401)->withBody($this->factory->createStream(json_encode(['type' => 'error', 'message' => $e->getMessage(), 'code' => $e->getCode()])));
        } catch (Exception $e) {
            $code = ($e->getCode() > 100 && $e->getCode() < 511) ? $e->getCode() : 500;
            return $this->factory->createResponse($code)->withBody($this->factory->createStream(json_encode(['type' => 'error', 'message' => $e->getMessage()])));
        }
    }
}
