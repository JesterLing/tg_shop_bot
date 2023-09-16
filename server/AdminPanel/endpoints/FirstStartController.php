<?php

namespace AdminPanel\Endpoints;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

use AdminPanel\Model\Users;
use AdminPanel\Model\Settings;
use AdminPanel\Model\Admins;

use \InvalidArgumentException;

final class FirstStartController
{
    public function getState(ServerRequest $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $config = Settings::getOnly(['bot_username', 'first_start', 'api_key', 'type']);
        if (!$config['first_start']) return $response->withBody(Utils::streamFor(json_encode(['type' => 'redirect'])));

        if ($params['step'] == 1) {
            $response->getBody()->write(json_encode(['step' => 1, 'data' => ['bot_username' => $config['bot_username'], 'api_key' => $config['api_key']]]));
            return $response;
        } else if ($params['step'] == 2) {
            $data = Users::getAll();
            $response->getBody()->write(json_encode(['step' => 2, 'data' => ['users' => $data]]));
            return $response;
        } else if ($params['step'] == 3) {
            $response->getBody()->write(json_encode(['step' => 3, 'data' => ['type' => $config['type']]]));
            return $response;
        } else {
            throw new InvalidArgumentException('Шаг номер ' . $params['step'] . ' не найден');
        }
    }

    public function setState(ServerRequest $request, Response $response): Response
    {
        $params = json_decode($request->getBody()->getContents(), true);
        $config = Settings::getOnly(['first_start']);
        if (!$config['first_start']) return $response->withBody(Utils::streamFor(json_encode(['type' => 'redirect'])));
        if ($params['step'] == 1) {
            if (empty($params['bot_username'] || empty($params['api_key']))) throw new InvalidArgumentException('Не найден параметр admins');
            $telegram = new \Longman\TelegramBot\Telegram($params['api_key'], $params['bot_username']);
            if (substr($_ENV['APP_URL'], -1) == '/') $url = $_ENV['APP_URL'];
            else $url = $_ENV['APP_URL'] . '/';
            $url = $url . 'hook.php';
            $url = preg_replace('/^http:/i', 'https:', $url);
            $result = $telegram->setWebhook($url, ['certificate' => '../ssl/public.pem']);
            if ($result->isOk()) {
                Settings::saveOnly(['bot_username' => $params['bot_username'], 'api_key' => $params['api_key']]);
                $response->getBody()->write(json_encode(['type' => 'success']));
            } else {
                $response->getBody()->write(json_encode(['type' => 'error', 'message' => 'Ошибка при попытке установить Webhook']));
            }
            return $response;
        } else if ($params['step'] == 2) {
            if (empty($params['admins'])) throw new InvalidArgumentException('Не найден параметр admins');
            Admins::clear();
            foreach ($params['admins'] as $id) {
                Admins::addNew($id);
            }
            $response->getBody()->write(json_encode(['type' => 'success']));
            return $response;
        } else if ($params['step'] == 3) {
            if (empty($params['type'])) throw new InvalidArgumentException('Не найден параметр type');
            Settings::saveOnly(['type' => $params['type'], 'first_start' => 0]);
            return $response->withBody(Utils::streamFor(json_encode(['type' => 'redirect'])));
        } else {
            throw new InvalidArgumentException('Шаг номер ' . $params['step'] . ' не найден');
        }
    }
}
