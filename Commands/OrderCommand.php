<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Entities\InlineKeyboard;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;

use Longman\TelegramBot\DB;
use PDO;

use TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;


class OrderCommand extends SystemCommand
{

    protected $name = 'order';
    protected $description = 'Оформить заказ';
    protected $usage = '/order';
    protected $version = '1.0.0';
    protected $conversation;

    public function execute(): ServerResponse
    {   
        $message = $this->getMessage();
        if($message == NULL) { 
            $message = $this->getCallbackQuery()->getMessage();
            $user_id = $this->getCallbackQuery()->getFrom()->getId();

            Request::editMessageText( [
                    'chat_id'    => $message->getChat()->getId(),
                    'message_id' => $message->getMessageId(),
                    'text'       => $message->getText(true),
            ]);
        } else {
            $user    = $message->getFrom();
            $user_id = $user->getId();
        }

        $chat    = $message->getChat();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        $state = $notes['state'] ?? 0;

        $result = Request::emptyResponse();

        switch ($state) {
            case 0:

                if($message->getContact() !== null) {

                    $notes['phone'] = $message->getContact()->getPhoneNumber();

                } elseif ($text !== '' && preg_match("/^\+380\d{3}\d{2}\d{2}\d{2}$/", $text)){

                    $notes['phone'] = $text;
                    $text = '';

                } else {

                    $notes['state'] = 0;
                    $this->conversation->update();

                    $data = [
                        'text' => 'Нажми поделится номером телефона или введи номер вручную в формате <b>+380*********</b>:',
                        'chat_id'      => $chat_id,
                        'parse_mode' => 'html',
                        'reply_markup' => (new Keyboard((new KeyboardButton('Поделится номером телефона'))->setRequestContact(true), ['Отмена']))->setResizeKeyboard(true)->setOneTimeKeyboard(true),
                    ];

                    $result = Request::sendMessage($data);
                    break;
                }
                
            case 1:
                if (in_array($text, ['По адресу', 'Самовывоз'], true)) {
                    if($text == 'По адресу') {
                        $notes['delivery'] = 1;
                    }
                    if($text == 'Самовывоз') {
                        $notes['delivery'] = 0;
                    }
                } else {

                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data = [
                        'text' => "Как доставить товар?\nДоставка на адрес доплачивается по тарифам такси",
                        'chat_id'      => $chat_id,
                        'reply_markup' => (new Keyboard(['По адресу'], ['Самовывоз'], ['Отмена']))->setResizeKeyboard(true)->setOneTimeKeyboard(true),
                    ];


                    $result = Request::sendMessage($data);
                    break;
                }
                $text = '';

            case 2:

            if($notes['delivery']) {
                if ($message->getLocation() !== null) {
                    $notes['longitude'] = $message->getLocation()->getLongitude();
                    $notes['latitude']  = $message->getLocation()->getLatitude();
                } else if($text !== '') {
                    $notes['address'] = $text;
                    $text = '';
                } else {

                    $notes['state'] = 2;
                    $this->conversation->update();

                    $data = [
                        'text' => "Введите адрес для доставки или поделитесь своим текущим местоположением:",
                        'chat_id'      => $chat_id,
                        'reply_markup' => (new Keyboard((new KeyboardButton('Поделится местоположением'))->setRequestLocation(true), ['Отмена']))->setResizeKeyboard(true)->setOneTimeKeyboard(true),
                    ];

                    $result = Request::sendMessage($data);
                    break;
                }
            }

            case 3:
                $this->conversation->update();
                unset($notes['state']);

                $message = "Заказ  \xF0\x9F\x95\x90 ".date("d-m-Y H:i:s")."\nДоставка: ";
                if($notes['delivery']) {
                    if(empty($notes['address'])) {
                        $nodes['address'] = $notes['latitude'].",".$notes['longitude'];
                        $message .= "\xF0\x9F\x93\x8D <a href=\"https://www.google.com/maps/search/?api=1&query=".$nodes['address'] ."\">Геопозиция</a>";
                    } else {
                        $message .= "\xF0\x9F\x9A\x9A <a href=\"https://www.google.com/maps/search/?api=1&query=".$notes['address']."\">".$notes['address']."</a>";
                    }
                } else {
                    $message .= "\xF0\x9F\x91\xA3 Самовывоз";
                }
                if($user->getUsername() == NULL) {
                    $message .= "\nПользователь: \xF0\x9F\x91\xA4 <a href=\"tg://user?id=".$user_id."\">".$user_id."</a>\n";
                } else {
                    $message .= "\nПользователь: \xF0\x9F\x91\xA4 <a href=\"tg://user?id=".$user_id."\">@".$user->getUsername()."</a>\n";
                }

                $message .= "Номер: \xF0\x9F\x93\xB1 ".$notes['phone']."\n------\n";

                $order = CartCommand::getOrder($user_id);
                self::processOrder($user_id, $notes);

                $amount = 0;
                foreach ($order as $row) {
                    $sum = $row['quantity']*$row['price'];
                    $message .= "\n";
                    if($row['id'] != 1) $message .= $row['name_m'].' ';
                    $message .= $row['title']."\n".$row['quantity'].' шт x '.$row['price'].' грн = '.$sum." грн";
                    if(isset($row['percent'])) {
                        if($pos['quantity'] >= $pos['discount']) {
                            $message .= " -".$pos['percent']."% = ";
                            $sum -= ($sum * ($pos['percent'] / 100));
                            $message .= $sum." грн";
                        }
                    }
                    $message .= "\n";
                    $amount += $sum;
                }
                $message .= "\n------\nВсего \xF0\x9F\x92\xB5 ".$amount." грн.";

                Request::sendMessage([
                    'text' => $message,
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                    'chat_id' => $this->getTelegram()->getAdminList()[0],
                ]);

                $data = [
                    'text' => AdminCommand::getSetting('successorder'),
                    'chat_id'      => $chat_id,
                    'reply_markup' =>  (new Keyboard(["\xF0\x9F\x92\xA8 Табаки", "\xE2\xAC\x9B Угли"],["\xF0\x9F\x9B\x92 Корзина", "\xF0\x9F\x93\x85 История"]))->setResizeKeyboard(true),
                ];

                $this->conversation->stop();

                $result = Request::sendMessage($data);

                break;
        }

        return $result;
    }

    public static function processOrder($user_id, $notes)
    {
        if(!isset($notes['adrress'])) $notes['adrress'] = null;
        return DB::getPdo()->query('UPDATE orders SET number = \''.$notes['phone'].'\', date = NOW(), delivery = \''.$notes['delivery'].'\', address = \''.$notes['address'].'\', complete = 1 WHERE user_id = '.$user_id.' AND complete = 0');
    }
}
