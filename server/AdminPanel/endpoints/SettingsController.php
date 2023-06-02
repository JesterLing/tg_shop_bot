<?php

namespace AdminPanel\Endpoints;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Response;

use AdminPanel\Model\Files;
use AdminPanel\Model\Settings;
use AdminPanel\Model\Admins;
use AdminPanel\Module\Exchange;

final class SettingsController
{
    public function get(ServerRequest $request, Response $response): Response
    {
        $settings = Settings::getAll();
        $settings['api_key'] = base64_encode($settings['api_key']);
        $settings['crystalpay_key'] = base64_encode($settings['crystalpay_key']);
        $settings['qiwi_private_key'] = base64_encode($settings['qiwi_private_key']);
        $settings['coinbase_key'] = base64_encode($settings['coinbase_key']);
        $response->getBody()->write(json_encode($settings));
        return $response;
    }

    public function save(ServerRequest $request, Response $response): Response
    {
        $params = json_decode($request->getBody()->getContents(), true);
        $update = [];
        $current = Settings::getOnly(['currency', 'type']);
        if ($params['currency'] != $current['currency']) {

            $exchange = new Exchange();
            $from = Exchange::convertISO4217($current['currency']);
            $to = Exchange::convertISO4217($params['currency']);
            Settings::changeCurrency($from, $to, $exchange);

            $update['currency'] = Exchange::convertInternal($params['currency']);
        }
        if ($params['type'] != $current['type']) {
            $update['type'] = $params['type'];
        }
        $params['api_key'] = base64_decode($params['api_key']);
        $params['crystalpay_key'] = base64_decode($params['crystalpay_key']);
        $params['qiwi_private_key'] = base64_decode($params['qiwi_private_key']);
        $params['coinbase_key'] = base64_decode($params['coinbase_key']);
        Settings::saveAll($params);
        $response->getBody()->write(json_encode(['type' => 'success', 'update' => $update]));
        return $response;
    }

    public function getAdmins(ServerRequest $request, Response $response): Response
    {
        $admins = Admins::getAll();
        $response->getBody()->write(json_encode($admins));
        return $response;
    }

    public function addAdmin(ServerRequest $request, Response $response): Response
    {
        $params = json_decode($request->getBody()->getContents(), true);
        $photo = null;
        $config = Settings::getOnly(['api_key', 'bot_username']);
        $tg = new \Longman\TelegramBot\Telegram($config['api_key'], $config['bot_username']);
        $photo_response = \Longman\TelegramBot\Request::getUserProfilePhotos([
            'user_id' => $params['user_id'],
            'limit'   => 1,
            'offset'  => null
        ]);
        if ($photo_response->isOk()) {
            $photos = $photo_response->getResult()->getPhotos();
            if (!empty($photos)) {
                $file_id = end($photos[0])->getFileId();
                $file = Files::getFileByTgId($file_id);
                if (!empty($file)) {
                    $photo = $file['id'];
                } else {
                    $file = \Longman\TelegramBot\Request::getFile(['file_id' => $file_id]);
                    $tg->setDownloadPath('./Download');
                    if ($file->isOk() && \Longman\TelegramBot\Request::downloadFile($file->getResult())) {
                        $photo = Files::insertFile(Files::generateFilename(), $file->getResult()->getFileSize(), '/Download/' . $file->getResult()->getFilePath(), $file->getResult()->getFileId());
                    }
                }
            }
        }
        Admins::addNew($params['user_id'], $photo);
        $response->getBody()->write(json_encode(['type' => 'success']));
        return $response;
    }

    public function delAdmin(ServerRequest $request, Response $response): Response
    {
        $params = json_decode($request->getBody()->getContents(), true);
        Admins::delByID($params['id']);
        $response->getBody()->write(json_encode(['type' => 'success']));
        return $response;
    }
}
