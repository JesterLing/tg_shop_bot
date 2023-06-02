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
        return $this->replyToChat($this->getConfig('greetings'), [
            'reply_markup' => self::getKeyboard($this->getConfig('type')),
        ]);
    }

    public static function getKeyboard($type)
    {
        if ($type) {
            $keyboard = new Keyboard(
                ["\xF0\x9F\x93\x9D Каталог", "Профиль"],
                ["\xE2\xAC\x9B История покупок", "\xF0\x9F\x93\x85 Поддержка"]
            );
        } else {
            $keyboard = new Keyboard(
                ["\xF0\x9F\x93\x9D Каталог", "\xF0\x9F\x9B\x92 Корзина"],
                ["\xE2\xAC\x9B История покупок", "\xF0\x9F\x93\x85 Поддержка"]
            );
        }
        $keyboard->setResizeKeyboard(true);
        return $keyboard;
    }
}
