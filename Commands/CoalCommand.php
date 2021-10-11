<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Entities\InlineKeyboard;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;

use Longman\TelegramBot\DB;
use PDO;

class CoalCommand extends SystemCommand
{

    protected $name = 'coal';
    protected $description = 'Угли';
    protected $usage = '/coal';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {   

        $user_id = $this->getMessage()->getFrom()->getId();
        $chat_id =  $this->getMessage()->getChat()->getId();


        $data = [
            'text' => 'Угли',
            'chat_id' => $chat_id,
            'reply_markup' => self::getInlineCoals($user_id),
        ];


        $result = Request::sendMessage($data);


        return $result;
    }

    public static function getInlineCoals($user_id)
    {
        $coals = CartCommand::getCoals($user_id);
        $price = EditCommand::getProduct(1);

        $keyboard = new InlineKeyboard(
                [['text' => 'Кокосовый уголь '.$price['price'].' грн. У вас '.$coals.' шт', 'callback_data' => 'null']],
                [['text' => "\xE2\x9E\x96", 'callback_data' => 'command=remove_coal'], ['text' => "\xE2\x9E\x95", 'callback_data' => 'command=add_coal']],
            );
        return $keyboard;
    }
}
