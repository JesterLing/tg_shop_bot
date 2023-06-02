<?php

namespace AdminPanel\Endpoints;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

use AdminPanel\Model\Users;
use AdminPanel\Model\Settings;

final class UsersController
{
    public function get(ServerRequest $request, Response $response): Response
    {
        $users = Users::getAll();
        $response->getBody()->write(json_encode($users));
        return $response;
    }

    public function mailing(ServerRequest $request, Response $response): Response
    {
        $params = json_decode($request->getBody()->getContents(), true);
        $config = Settings::getOnly(['api_key', 'bot_username']);
        $tg = new \Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);
        $tg->enableExternalMySql(\AdminPanel\Module\DB::getPdo());
        $results = \Longman\TelegramBot\Request::sendToActiveChats(
            'sendMessage',
            [
                'text' => $params['message'],
                'parse_mode' => 'html',
            ],
            [
                'groups'      => false,
                'supergroups' => false,
                'channels'    => false,
                'users'       => true,
            ]
        );

        if (empty($results)) {
            $response->getBody()->write(json_encode(['type' => 'error', 'message' => 'Не найдено чатов или пользователей']));
            return $response;
        }

        $success  = 0;
        $failed = 0;

        foreach ($results as $result) $result->isOk() ? ++$success : ++$failed;

        $response->getBody()->write(json_encode(['type' => 'success', 'message' => 'Успешно выполнено! Отправлено: ' . $success . '. С ошибкой: ' . $failed]));
        return $response;
    }
}
