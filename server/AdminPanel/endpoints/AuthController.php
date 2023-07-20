<?php

namespace AdminPanel\Endpoints;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

use AdminPanel\Model\Admins;
use AdminPanel\Model\Settings;
use AdminPanel\Module\Exchange;

use \Exception;
use \InvalidArgumentException;

final class AuthController
{
	public function auth(ServerRequest $request, Response $response): Response
	{
		$params = json_decode($request->getBody()->getContents(), true);
		if (empty($params['secret'])) throw new InvalidArgumentException('Не указан параметр secret');

		$admins = Admins::getBySecret($params['secret']);

		if (empty($admins)) throw new InvalidArgumentException('Неверная ссылка для входа');
		if (strtotime($admins['secret_expired']) < time()) throw new Exception('Ссылка для входа больше не действительна', 401);

		$now = new \DateTimeImmutable();
		$jwt = $this->generateToken($admins['id'], $now);

		$refresh_token = bin2hex(random_bytes(10));
		Admins::editRefreshByID($refresh_token, $admins['id']);
		$conf = Settings::getOnly(['type', 'currency']);
		$conf['currency'] = Exchange::convertInternal($conf['currency']);
		$body = Utils::streamFor(json_encode([
			'type' => 'success',
			'refresh_token' => $refresh_token,
			'user' => [
				'user_id' => $admins['user_id'],
				'username' => $admins['username'],
				'first_name' => $admins['first_name'],
				'last_name' => $admins['last_name'],
				'photo' => $admins['path']
			],
			'configs' => $conf
		]));
		return $response->withHeader('Set-Cookie', (string)$jwt)->withBody($body);
	}

	public function refresh(ServerRequest $request, Response $response): Response
	{
		$params = json_decode($request->getBody()->getContents(), true);
		$cookie = $request->getCookieParams();
		if (empty($params['refresh'])) throw new InvalidArgumentException('Не указан параметр refresh');
		if (empty($cookie['jwt'])) throw new Exception('Не найден старый токен', 401);
		try {
			$jwt = (array)JWT::decode($cookie['jwt'], new Key($_ENV['KEY'], 'HS256'));
			throw new InvalidArgumentException('Токен все еще активен');
		} catch (ExpiredException $e) {
			$tks = explode('.', $cookie['jwt']);
			$payload = JWT::jsonDecode(JWT::urlsafeB64Decode($tks[1]));
			$result = Admins::getByID($payload->aud);
			if ($params['refresh'] != $result['refresh_token']) throw new InvalidArgumentException('Неверный refresh токен');
			$jwt = $this->generateToken($payload->aud);
			return $response->withHeader('Set-Cookie', (string)$jwt)->withBody(Utils::streamFor(json_encode(['type' => 'success'])));
		}
	}

	private function generateToken($aud, $time = null)
	{
		if (is_null($time)) $time = new \DateTimeImmutable();
		$payload = [
			'aud' => $aud,
			'iss' => $_SERVER['SERVER_NAME'],
			'iat' => $time->getTimestamp(),
			'exp' => $time->modify('+10 minutes')->getTimestamp()
		];
		$jwt = JWT::encode($payload, $_ENV['KEY'], 'HS256');
		$cookie = new setCookie([
			'Name' => 'jwt',
			'Value' => $jwt,
			'Expires' => $time->modify('+1 year')->getTimestamp(),
			'HttpOnly' => true
		]);
		return $cookie;
	}
}
