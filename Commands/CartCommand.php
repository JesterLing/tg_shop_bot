<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Entities\InlineKeyboard;

use Longman\TelegramBot\DB;
use PDO;

class CartCommand extends SystemCommand
{

    protected $name = 'cart';
    protected $description = 'Корзина с товарами';
    protected $usage = '/cart';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {   
        $user_id = $this->getMessage()->getFrom()->getId();
        
        $order = self::getOrder($user_id);
        if(!empty($order)) {
            $text = "\n\n-----";
            $amount = 0;

        foreach ($order as $row) {
            $sum = $row['quantity']*$row['price'];
            $text .= "\n";
            if($row['id'] != 1) $text .= $row['name_m'].' ';
            $text .= $row['title']."\n".$row['quantity'].' шт x '.$row['price'].' грн = '.$sum." грн";
            if(isset($row['percent'])) {
              $text .= " -".$row['percent']."% = ";
              $sum -= ($sum * ($row['percent'] / 100));
              $text .= $sum." грн";
            }
            $text .= "\n";
            $amount += $sum;
        }
            $text .= "-----\n\nВсего ".$amount." грн";

            return $this->replyToChat($text, [
               'parse_mode' => 'html',
               'reply_markup' => new InlineKeyboard(
                [['text' => "\xE2\x9C\x85 Перейти к оформлению", 'callback_data' => 'command=order']],
                [['text' => "\xf0\x9f\x97\x91 Очистить корзину", 'callback_data' => 'command=clear']],
            ),
            ]);
        } else {
            $text = "Корзина пуста";
            return $this->replyToChat($text);
        }
    }


    public static function addToCart($user_id, $product_id)
    {

       DB::getPdo()->query('INSERT INTO orders (user_id) SELECT '.$user_id.' WHERE NOT EXISTS (SELECT id FROM orders WHERE user_id='.$user_id.' AND complete=0)');
       $lastIsert = DB::getPdo()->lastInsertId();
       if($lastIsert == 0) {

            $nRows = DB::getPdo()->query('UPDATE positions SET quantity = quantity + 1 WHERE order_id IN (SELECT id FROM orders WHERE user_id = '.$user_id.' AND complete = 0) AND product_id = '.$product_id);
            if($nRows->rowCount() < 1) {
                DB::getPdo()->query('INSERT INTO positions (order_id, product_id, quantity) SELECT id, '.$product_id.', 1 FROM orders WHERE user_id = '.$user_id.' AND complete = 0');
            }

       } else {
             DB::getPdo()->query('INSERT INTO positions (order_id, product_id, quantity) VALUES ('.$lastIsert.', '.$product_id.', 1)');
       }
    }
    public static function getCoals($user_id)
    {
      $ret = DB::getPdo()->query('SELECT quantity FROM positions WHERE order_id IN (SELECT id FROM orders WHERE user_id = '.$user_id.' AND complete = 0) AND product_id = 1')->fetchAll(PDO::FETCH_ASSOC);
      if(!isset($ret[0]['quantity'])) return 0;
      else return $ret[0]['quantity'];
    }
    
    public static function deleteFromCart($user_id, $product_id)
    {
      $order_id = DB::getPdo()->query('SELECT id FROM orders WHERE user_id = '.$user_id.' AND complete = 0')->fetchAll(PDO::FETCH_ASSOC);
      if(!isset($order_id[0]['id'])) return false;

      $qa = DB::getPdo()->query('SELECT quantity FROM positions WHERE order_id IN (SELECT id FROM orders WHERE user_id = '.$user_id.' AND complete = 0) AND product_id = 1')->fetchAll(PDO::FETCH_ASSOC);
      if(!isset($qa[0]['quantity'])) return false;

      if($qa[0]['quantity'] > 1) {
        return DB::getPdo()->query('UPDATE positions SET quantity = quantity - 1 WHERE order_id = '.$order_id[0]['id'].' AND product_id = '.$product_id);
      } else {
        return DB::getPdo()->query('DELETE FROM positions WHERE order_id = '.$order_id[0]['id'].' AND product_id = '.$product_id);
      }
      
    }

    public static function getOrder($user_id)
    {
       return DB::getPdo()->query('SELECT positions.quantity, manufacturers.name_m, products.* FROM positions JOIN products ON positions.product_id = products.id JOIN manufacturers ON products.manufacturer_id = manufacturers.id OR products.manufacturer_id = 0 JOIN orders ON positions.order_id = orders.id AND orders.complete = 0 AND orders.user_id = '.$user_id.' GROUP BY products.title')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function clearCart($user_id) 
    {
       return DB::getPdo()->query('DELETE FROM orders WHERE user_id = '.$user_id.' AND complete = 0');
    }
}
