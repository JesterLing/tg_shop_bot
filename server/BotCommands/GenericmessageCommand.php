<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Entities\CallbackQuery;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Conversation;

class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';

    /**
     * @var string
     */
    protected $description = 'Handle generic message';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * Main command execution
     *
     * @return ServerResponse
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $message_text = $message->getText(true);


        switch($message_text) {
            case "\xF0\x9F\x93\x9D Каталог":
               $this->telegram->executeCommand('categories');
            break;
            case "\xF0\x9F\x9B\x92 Корзина":
                $this->telegram->executeCommand('cart');
            break;
            case "Профиль":
                $this->telegram->executeCommand('profile');
            break;
            case "\xE2\xAC\x9B История покупок":
                $this->telegram->executeCommand('orders');
            break;
            case "\xF0\x9F\x93\x85 Поддержка":
                 $this->telegram->executeCommand('support');
            break;
        }

        $conversation = new Conversation(
            $message->getFrom()->getId(),
            $message->getChat()->getId()
        );
        if ($conversation->exists() && $command = $conversation->getCommand()) {
            return $this->telegram->executeCommand($command);
        }
        return Request::emptyResponse();
    }

}
