<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

use Longman\TelegramBot\DB;
use PDO;

use TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;

class AllordersCommand extends SystemCommand
{

    protected $name = 'allorders';
    protected $description = 'Все заказы';
    protected $usage = '/allorders';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $user_id = $this->getMessage()->getFrom()->getId();
        if($this->getTelegram()->isAdmin($user_id)) {
            if ($pagination = self::getInlineKeyboardAllOrders()) {
                if(count($pagination['keyboard']) > 1) $keyboard['inline_keyboard'][] = $pagination['keyboard'];
                return $this->replyToChat(self::getPaginationAllOrders($pagination['items']), [
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                    'reply_markup' => $keyboard,
                ]);
            } else {
                return $this->replyToChat("Заказов еще не было");
            }
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

    public static function getAllOrders()
    {
        $result = array();
        $all = DB::getPdo()->query('SELECT positions.quantity, positions.product_id, manufacturers.name_m, products.*, orders.*, user.username FROM positions JOIN products ON positions.product_id = products.id JOIN manufacturers ON products.manufacturer_id = manufacturers.id OR products.manufacturer_id = 0 JOIN orders ON positions.order_id = orders.id AND orders.complete = 1 JOIN user ON orders.user_id = user.id GROUP BY positions.id ORDER BY orders.date DESC')->fetchAll(PDO::FETCH_ASSOC);
        foreach($all as $row) {
            if(!array_key_exists($row['id'], $result)) {
                $result[$row['id']] = [
                    'date' => $row['date'],
                    'user_id' => $row['user_id'],
                    'username' => $row['username'],
                    'delivery' => $row['delivery'],
                    'address' => $row['address'],
                    'number' => $row['number']
                ];
            }
            if($row['product_id'] == 1) $row['name_m'] = '';
            $result[$row['id']]['positions'][] = [
                'id' => $row['product_id'],
                'name_m' => $row['name_m'],
                'title' => $row['title'],
                'price' => $row['price'],
                'discount' => $row['discount'],
                'percent' => $row['percent'],
                'quantity' => $row['quantity']
            ];

        }
        return $result;
    }


    public static function getPaginationAllOrders(array $items, $page = 1)
    {
        if(empty($items)) return "Заказов еще не было";
        $message = "";
        foreach($items as $row) {
            $message = "Заказ \xF0\x9F\x95\x90 ".date ('d-m-Y H:i', strtotime($row['date']))."\nДоставка: ";
            if($row['delivery']) {
                if(preg_match('/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/', $row['address'])) {
                    $message .= "\xF0\x9F\x93\x8D <a href=\"https://www.google.com/maps/search/?api=1&query=".$row['address']."\">Геопозиция</a>";
                } else {
                    $message .= "\xF0\x9F\x9A\x9A <a href=\"https://www.google.com/maps/search/?api=1&query=".$row['address']."\">".$row['address']."</a>";
                }
            } else {
                $message .= "\xF0\x9F\x91\xA3 Самовывоз";
            }
            if(!isset($row['username'])) {
                $message .= "\nПользователь: \xF0\x9F\x91\xA4 <a href=\"tg://user?id=".$row['user_id']."\">".$row['user_id']."</a>";
            } else {
                $message .= "\nПользователь: \xF0\x9F\x91\xA4 <a href=\"tg://user?id=".$row['user_id']."\">@".$row['username']."</a>";
            }
            $message .= "\nНомер: \xF0\x9F\x93\xB1 ".$row['number']."\n------\n";
            $amount = 0;
            foreach ($row['positions'] as $pos) {
                $sum = $pos['quantity']*$pos['price'];
                $message .= "\n";
                if($pos['id'] != 1) $message .= $pos['name_m'].' ';
                $message .= $pos['title']."\n".$pos['quantity'].' шт x '.$pos['price'].' грн = '.$sum." грн";
                if(isset($pos['percent'])) {
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
        }
        return $message;
    }

    public static function getInlineKeyboardAllOrders($page = 1)
    {
        $odr = self::getAllOrders();

        if (empty($odr)) {
            return null;
        }
        $ikp = new InlineKeyboardPagination($odr, 'admin_all_orders', $page, 1);
        try {
            $pagination = $ikp->getPagination();
        } catch (InlineKeyboardPaginationException $e) {
            $pagination = $ikp->getPagination(1);
        }
        return $pagination;
    }

}
