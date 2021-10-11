<?php
namespace Longman\TelegramBot\Commands\SystemCommand;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class ExitCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'exit';

    /**
     * @var string
     */
    protected $description = 'Выйти из админки';

    /**
     * @var string
     */
    protected $usage = '/exit';

    /**
     * @var string
     */
    protected $version = '0.3.0';

    public function execute(): ServerResponse
    {

        $keyboard = new Keyboard(
            ["\xF0\x9F\x92\xA8 Табаки", "\xE2\xAC\x9B Угли"],
            ["\xF0\x9F\x9B\x92 Корзина", "\xF0\x9F\x93\x85 История"]
        );

        $keyboard->setResizeKeyboard(true);
        return $this->replyToChat('Готово', [
            'reply_markup' => $keyboard,
        ]);
    }
}