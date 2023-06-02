<?php

namespace AdminPanel\Middleware;

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use \Firebase\JWT\ExpiredException;
use \Firebase\JWT\SignatureInvalidException;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

use GuzzleHttp\Psr7\HttpFactory;

use \DomainException;
use \UnexpectedValueException;

class AuthMiddleware
{
    private $attribute_name = 'auth';
    private $factory;

    public function __construct(HttpFactory $responseFactory)
    {
        $this->factory = $responseFactory;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = $request->getAttribute($this->attribute_name);
        if ($auth) {
            $cookie = $request->getCookieParams();
            if (empty($cookie['jwt'])) {
                return $this->factory->createResponse(400)->withBody($this->factory->createStream(json_encode(['type' => 'error', 'message' => 'В запросе отсутствует токен'])));
            }
            try {
                $token = JWT::decode($cookie['jwt'], new Key(KEY, 'HS256'));
                $request = $request->withAttribute($this->attribute_name, [$auth, $token->aud]);
                return $handler->handle($request);
            } catch (ExpiredException $e) {
                return $this->factory->createResponse(401)->withBody($this->factory->createStream(json_encode(['type' => 'error', 'message' => 'Предоставленный токен истек', 'code' => $e->getCode()])));
            } catch (DomainException $e) {
                return $this->factory->createResponse(401)->withBody($this->factory->createStream(json_encode(['type' => 'error', 'message' => 'Задан некорректный ключ JWT (файл config.php)', 'code' => $e->getCode()])));
            } catch (SignatureInvalidException | UnexpectedValueException $e) {
                return $this->factory->createResponse(401)->withBody($this->factory->createStream(json_encode(['type' => 'error', 'message' => 'Предоставленный токен некорректный', 'code' => $e->getCode()])));
            }
        } else {
            return $handler($request);
        }
    }
}
