<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

use TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;
use Longman\TelegramBot\Entities\InlineKeyboard;

use AdminPanel\Model\Cart;

/**
 * Взаимодействие с корзиной
 */
class CartCommand extends SystemCommand
{

  protected $name = 'cart';
  protected $description = 'Корзина';
  protected $usage = '/cart';
  protected $version = '1.0.0';

  public function execute(): ServerResponse
  {
    if ($this->getConfig('type')) return Request::emptyResponse();

    if ($this->getCallbackQuery() !== null) {

      $message['chat_id'] = $this->getCallbackQuery()->getMessage()->getChat()->getId();
      $message['message_id'] = $this->getCallbackQuery()->getMessage()->getMessageId();
      $message['parse_mode'] = 'html';
      $message['reply_markup'] = new InlineKeyboard(
        [['text' => "\xE2\x9C\x85 Перейти к оформлению", 'callback_data' => 'c=so']],
        [['text' => "	\xE2\x9C\x8F Редактировать корзину", 'callback_data' => 'c=ec'], ['text' => "\xf0\x9f\x97\x91 Очистить корзину", 'callback_data' => 'c=cc']],
      );
      $message['text'] = $this->buildCart(Cart::getAllByUserID($this->getCallbackQuery()->getFrom()->getId()));
      return Request::editMessageText($message);
    } else {
      $items = Cart::getAllByUserID($this->getMessage()->getFrom()->getId());
      if (empty($items)) return $this->replyToUser('Здесь пока пусто');
      $message = $this->buildCart($items);
      return $this->replyToUser($message, [
        'parse_mode' => 'html',
        'reply_markup' => new InlineKeyboard(
          [['text' => "\xE2\x9C\x85 Перейти к оформлению", 'callback_data' => 'c=so']],
          [['text' => "	\xE2\x9C\x8F Редактировать корзину", 'callback_data' => 'c=ec'], ['text' => "\xf0\x9f\x97\x91 Очистить корзину", 'callback_data' => 'c=cc']],
        ),
      ]);
    }

    // $user_id = $this->getMessage()->getFrom()->getId();

    // $order = self::getOrder($user_id);
    // if(!empty($order)) {
    //     $text = "\n\n-----";
    //     $amount = 0;

    // foreach ($order as $row) {
    //     $sum = $row['quantity']*$row['price'];
    //     $text .= "\n";
    //     if($row['id'] != 1) $text .= $row['name_m'].' ';
    //     $text .= $row['title']."\n".$row['quantity'].' шт x '.$row['price'].' грн = '.$sum." грн";
    //     if(isset($row['percent'])) {
    //       $text .= " -".$row['percent']."% = ";
    //       $sum -= ($sum * ($row['percent'] / 100));
    //       $text .= $sum." грн";
    //     }
    //     $text .= "\n";
    //     $amount += $sum;
    // }
    //     $text .= "-----\n\nВсего ".$amount." грн";

    //     return $this->replyToChat($text, [
    //        'parse_mode' => 'html',
    //        'reply_markup' => new InlineKeyboard(
    //         [['text' => "\xE2\x9C\x85 Перейти к оформлению", 'callback_data' => 'c=so']],
    //         [['text' => "\xf0\x9f\x97\x91 Очистить корзину", 'callback_data' => 'command=clear']],
    //     ),
    //     ]);
    // } else {
    //     $text = "Корзина пуста";

    // }
  }

  private function buildCart(array $items)
  {
    $message = "<b>Корзина</b>\n";
    $amount = 0;
    foreach ($items as $row) {
      $discount = 0;
      $message .= "\n" . $row['pname'] . ' ' . $row['price'] . $this->getConfig('currency') . '. x' . $row['quantity'] . 'шт.';
      if (!is_null($row['dis_percent']) && !is_null($row['discount'])) {
        if ($row['quantity'] >= $row['discount']) {
          $discount = round(($row['price'] / 100) * $row['dis_percent']);
          $message .= " \xE2\x9D\x97\xF0\x9F\x8E\x81 -" . $row['dis_percent'] . '%';
        }
      }
      $amount += ($row['price'] - $discount) * $row['quantity'];
      $message .= ' ' . ($row['price'] - $discount) * $row['quantity'] . $this->getConfig('currency') . ".";
    }
    $message .= "\n\nВсего сумма: " . $amount . $this->getConfig('currency') . '.';
    return $message;
  }

  public static function buildEditCart(array $row, $currency)
  {
    $message = $row['pname'] . ' ' . $row['price'] . $currency . '. x' . $row['quantity'] . 'шт.';
    $discount = 0;
    if (!is_null($row['dis_percent']) && !is_null($row['discount'])) {
      if ($row['quantity'] >= $row['discount']) {
        $discount = round(($row['price'] / 100) * $row['dis_percent']);
        $message .= " \xE2\x9D\x97\xF0\x9F\x8E\x81 -" . $row['dis_percent'] . '%';
      }
      $message .= ' ' . ($row['price'] - $discount) * $row['quantity'] . $currency . ".";
    }
    return $message;
  }

  public static function getInlineKeyboardEditCart(array $items, $page = 1)
  {
    $ikp = new InlineKeyboardPagination($items, 'ec', $page, 1);
    $ikp->setCallbackDataFormat('c={COMMAND}&rp={OLD_PAGE}&np={NEW_PAGE}');
    try {
      $pagination = $ikp->getPagination();
    } catch (InlineKeyboardPaginationException $e) {
      $pagination = $ikp->getPagination(1);
    }
    return $pagination;
  }
}
