<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

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
        $text = 'Нечего отменять!';
        $conversation = new Conversation(
            $this->getMessage()->getFrom()->getId(),
            $this->getMessage()->getChat()->getId()
        );
        if ($conversation->getCommand()) {
            $conversation->cancel();
            $text = 'Отмена. Возврат в главное меню';
        }
        return $this->replyToChat($text, [
            'reply_markup' => StartCommand::getKeyboard($this->getConfig('type')),
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