<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Entities\InlineKeyboard;

use Longman\TelegramBot\DB;
use PDO;

class SettingsCommand extends SystemCommand
{

    protected $name = 'settings';
    protected $description = 'Настройки';
    protected $usage = '/settings';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $user_id = $this->getMessage()->getFrom()->getId();
        if($this->getTelegram()->isAdmin($user_id)) {
            return $this->replyToChat('Настройки ответов', [
                   'reply_markup' => new InlineKeyboard(
                    [['text' => 'Изменить сообщение приветствия', 'callback_data' => 'command=admin_change_start']],
                    [['text' => 'Изменить сообщение при успешном заказе', 'callback_data' => 'command=admin_change_succes']],
                ),
            ]);
        } else {
            $keyboard = (new Keyboard(
                ["\xF0\x9F\x92\xA8 Табаки", "\xE2\xAC\x9B Угли"],
                ["\xF0\x9F\x9B\x92 Корзина", "\xF0\x9F\x93\x85 История"],
            ))->setResizeKeyboard(true);
            return $this->replyToChat('Вы не аккаунте администратора', [
                'reply_markup' => $keyboard,
            ]);
        }
    }
}
