<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class StartCommand extends SystemCommand
{

    protected $name = 'start';
    protected $description = 'Start command';
    protected $usage = '/start';
    protected $version = '1.2.0';

    public function execute(): ServerResponse
    {

        $keyboard = new Keyboard(
            ["\xF0\x9F\x92\xA8 Табаки", "\xE2\xAC\x9B Угли"],
            ["\xF0\x9F\x9B\x92 Корзина", "\xF0\x9F\x93\x85 История"]
        );

        $keyboard->setResizeKeyboard(true);

        return $this->replyToChat(AdminCommand::getSetting('greetings'), [
            'reply_markup' => $keyboard,
        ]);
    }
}