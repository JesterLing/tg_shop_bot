<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;

use AdminPanel\Model\Users;

/**
 * Баланс, статистика и профиль пользователя
 */
class ProfileCommand extends SystemCommand
{

    protected $name = 'profile';
    protected $description = 'Профиль';
    protected $usage = '/profile';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        if (!$this->getConfig('type')) return Request::emptyResponse();

        $user_info = Users::getByID($this->getMessage()->getFrom()->getId());

        if (empty($user_info['balance'])) $user_info['balance'] = 0;
        if (empty($user_info['orders'])) $user_info['orders'] = 0;

        return $this->replyToChat($this->buildMessage($user_info), [
            'parse_mode' => 'html',
            'reply_markup' => new InlineKeyboard([
                ['text' => "\xF0\x9F\x92\xB3 Пополнить баланс в боте", 'callback_data' => 'c=ab']
            ]),
        ]);
    }

    private function buildMessage($user_info)
    {
        $message = "";
        if (isset($user_info['username'])) {
            $message .= "\xF0\x9F\x91\xA4 Пользователь: <a href=\"tg://user?id=" . $user_info['id'] . "\">@" . $user_info['username'] . "</a>\n";
        } elseif (isset($user_info['first_name']) || isset($user_info['last_name'])) {
            $message .= "\xF0\x9F\x91\xA4 Пользователь: <a href=\"tg://user?id=" . $user_info['id'] . "\">" . $user_info['first_name'] . $user_info['last_name'] . "</a>\n";
        } else {
            $message .= "\xF0\x9F\x91\xA4 Пользователь: <a href=\"tg://user?id=" . $user_info['id'] . "\">" . $user_info['id'] . "</a>\n";
        }
        $message .= "\xF0\x9F\x92\xB8 Количество покупок: " . $user_info['orders'] . "\n";
        $message .= "\xF0\x9F\x92\xB0 Ваш баланс: " . $user_info['balance'] . " " . $this->getConfig('currency') . "\n";
        return $message;
    }
}
