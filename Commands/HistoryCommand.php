<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

use Longman\TelegramBot\DB;
use PDO;

use TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;

class HistoryCommand extends SystemCommand
{

    protected $name = 'history';
    protected $description = 'История заказов';
    protected $usage = '/history';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {

        if ($pagination = self::getInlineKeyboardHistory($this->getMessage()->getFrom()->getId())) {
           if(count($pagination['keyboard']) > 1) $keyboard['inline_keyboard'][] = $pagination['keyboard'];
            return $this->replyToChat(self::getPaginationHistory($pagination['items']),
                    [ 'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                    'reply_markup' => $keyboard,
            ]);
        } else {
            return $this->replyToChat("Заказов еще не было");
        }
    }

    public static function getHistoryOrders($user_id)
    {
        $orders = DB::getPdo()->query('SELECT * FROM orders WHERE user_id = '.$user_id.' AND orders.complete = 1');
        if($orders->rowCount() < 1) return false;

        $result = array();
        $all = DB::getPdo()->query('SELECT positions.quantity, positions.product_id, manufacturers.name_m, products.*, orders.* FROM positions JOIN products ON positions.product_id = products.id JOIN manufacturers ON products.manufacturer_id = manufacturers.id OR products.manufacturer_id = 0 JOIN orders ON positions.order_id = orders.id AND orders.complete = 1 AND orders.user_id = '.$user_id.' GROUP BY positions.id ORDER BY orders.date DESC')->fetchAll(PDO::FETCH_ASSOC);
        foreach($all as $row) {
            if(!array_key_exists($row['id'], $result)) {
                $result[$row['id']] = [
                    'date' => $row['date'],
                    'delivery' => $row['delivery'],
                    'address' => $row['address'],
                    'number' => $row['number']
                ];
            }
            if($row['product_id'] == 1) $row['name_m'] = '';
            $result[$row['id']]['positions'][] = [
                'id'   => $row['product_id'],
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


    public static function getPaginationHistory(array $items, $page = 1)
    {
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

    public static function getInlineKeyboardHistory($user_id, $page = 1)
    {
        $hst = self::getHistoryOrders($user_id);

        if (empty($hst)) {
            return null;
        }
        $ikp = new InlineKeyboardPagination($hst, 'history', $page, 1);
        try {
            $pagination = $ikp->getPagination();
        } catch (InlineKeyboardPaginationException $e) {
            $pagination = $ikp->getPagination(1);
        }
        return $pagination;
    }

}
