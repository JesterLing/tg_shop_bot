<?php

namespace AdminPanel\Endpoints;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

use AdminPanel\Model\Settings;

use \Exception;
use \PDOException;

final class DashboardController
{

    public function getInfo(ServerRequest $request, Response $response): Response
    {
        $result = [];

        try {
            $config = Settings::getAll();

            if (!$config['service']) {
                if (empty($config['api_key'])) throw new Exception('noToken');
                if (empty($config['bot_username'])) throw new Exception('noUsername');

                $tg = new \Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);
                $getMe = \Longman\TelegramBot\Request::getMe();

                if ($getMe->isOk()) {
                    $result['info']['bot'] = $getMe->getResult()->getFirstName();
                    $result['info']['nick'] = $getMe->getResult()->getUsername();
                    $result['info']['status'] = 'ok';
                } else {
                    throw new \Exception('APIError');
                }

                $getWebHookInfo = \Longman\TelegramBot\Request::getWebhookInfo();

                if ($getWebHookInfo->isOk()) {
                    // if(isset($result->last_error_message)) throw new \Exception($result->last_error_message);
                    if (empty($getWebHookInfo->getResult()->getUrl())) throw new \Exception('noWebhook');
                } else {
                    throw new \Exception('APIError');
                }
            } else {
                $result['info']['status'] = 'service';
            }
        } catch (PDOException $ex) {
            $result['info']['status'] = 'DBError';
        } catch (\Longman\TelegramBot\Exception\InvalidBotTokenException $ex) {
            $result['info']['status'] = 'invalidToken';
        } catch (Exception $ex) {
            $result['info']['status'] = $ex->getMessage();
        }

        if ($config['type']) $result['info']['type'] = 'Цифровые товары';
        else $result['info']['type'] = 'Физические товары';
        $result['info']['created'] = strtotime($config['created_at']);

        $sth = \AdminPanel\Module\DB::prepare('SELECT (SELECT COUNT(*) FROM user) AS users, (SELECT COUNT(*) FROM orders) AS purchases, (SELECT COUNT(*) FROM products) AS goods, (SELECT COUNT(*) FROM message) AS apis, admins.updated_at FROM `admins` WHERE admins.id = ?');
        $sth->execute([$request->getAttribute('auth')[1]]);
        $stats = $sth->fetch(\PDO::FETCH_ASSOC);

        $result['info']['lastLogIn'] = strtotime($stats['updated_at']);

        $result['stats']['users'] = $stats['users'];
        $result['stats']['purchases'] = $stats['purchases'];
        $result['stats']['goods'] = $stats['goods'];
        $result['stats']['apis'] = $stats['apis'];

        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
