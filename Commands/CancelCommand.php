<?php
namespace Longman\TelegramBot\Commands\SystemCommand;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

class CancelCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'cancel';

    /**
     * @var string
     */
    protected $description = 'Отмена активной conversation';

    /**
     * @var string
     */
    protected $usage = '/cancel';

    /**
     * @var string
     */
    protected $version = '0.3.0';

    public function execute(): ServerResponse
    {

        $conversation = new Conversation($this->getMessage()->getFrom()->getId(), $this->getMessage()->getChat()->getId());

        if ($conversation_command = $conversation->getCommand()) {
            $conversation->cancel();
        }

        if($conversation_command == 'order') { // MAIN KEYBOARD
            $keyboard = new Keyboard(
                ["\xF0\x9F\x92\xA8 Табаки", "\xE2\xAC\x9B Угли"],
                ["\xF0\x9F\x9B\x92 Корзина", "\xF0\x9F\x93\x85 История"]
            );
        } else { // ADMIN KEYBOARD
            $keyboard = new Keyboard(
                ['Добавить/удалить табаки'],
                ['Все заказы'],
                ['Настройки ответов'],
            );
        } 
        $keyboard->setResizeKeyboard(true);

        return $this->replyToChat('Отмена. Возврат в главное меню', [
            'reply_markup' => $keyboard,
        ]);
    }

    /**
     * Remove the keyboard and output a text.
     *
     * @param string $text
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    private function removeKeyboard($text): ServerResponse
    {
        return $this->replyToChat($text, [
            'reply_markup' => Keyboard::remove(['selective' => true]),
        ]);
    }
}