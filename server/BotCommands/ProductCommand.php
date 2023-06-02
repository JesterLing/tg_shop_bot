<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Conversation;

use TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;
use Longman\TelegramBot\Entities\InlineKeyboard;

use AdminPanel\Model\Products;
use AdminPanel\Model\Cart;

/**
 * Отображение товара, выбор количества/добавление в корзину
 */
class ProductCommand extends UserCommand
{

    protected $name = 'product';
    protected $description = 'Продукт';
    protected $usage = '/product';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        if ($this->getCallbackQuery() !== null) {

            $params = InlineKeyboardPagination::getParametersFromCallbackData($this->getCallbackQuery()->getData());

            $product = Products::getByID($params['id']);
            if (empty($params['qu'])) $params['qu'] = 1;
            $message = $this->buildProduct($product, $params['qu'], $params['rp']);

            $message['chat_id'] = $this->getCallbackQuery()->getMessage()->getChat()->getId();
            $message['message_id'] = $this->getCallbackQuery()->getMessage()->getMessageId();
            $message['disable_web_page_preview'] = false;
            $message['parse_mode'] = 'HTML';
            return Request::editMessageText($message);
        } else {

            $conversation = new Conversation($this->getMessage()->getFrom()->getId(), $this->getMessage()->getChat()->getId());
            if ($conversation->getCommand() == $this->getName()) {
                $quantity = trim($this->getMessage()->getText(true));
                if (is_numeric($quantity)) {
                    if ($quantity < 1) {
                        return $this->replyToChat('Количество должно быть > 0 Введите свое количество:');
                    }
                    $product = Products::getByID($conversation->notes['id']);
                    if (!is_null($product['quantity'])) {
                        if ($quantity > $product['quantity']) {
                            return $this->replyToChat('Такого количества товара нет в наличии. Введите свое количество:');
                        }
                    }
                    $conversation->notes['quantity'] = $quantity;
                    $conversation->update();

                    $message = $this->buildProduct($product, $conversation->notes['quantity'], $conversation->notes['rp']);

                    $message['chat_id'] = $this->getMessage()->getChat()->getId();
                    $message['disable_web_page_preview'] = false;
                    $message['parse_mode'] = 'HTML';
                    $conversation->stop();
                    return Request::sendMessage($message);
                } else {
                    return $this->replyToChat('Введите свое количество:');
                }
            }
            return $this->replyToChat('Недопустимый вызов команды');
        }
    }

    private function buildProduct(array $product_info, $quantity = null, $returnPage = 1)
    {
        $message['text'] = '';
        // if(!is_null($product_info['image'])) {
        //     $message['text'] .= '<a href="https://'.$_SERVER['HTTP_HOST'].'/Download/'.$product_info['image'].'">&#8205;</a>';
        // }
        $message['text'] .= "\xF0\x9F\x93\x83 Товар: " . $product_info['name'] . "\r\n";
        if (!is_null($product_info['quantity'])) $message['text'] .= "\xF0\x9F\x92\xAF Количество: " . $product_info['quantity'] . " шт.\r\n";
        $message['text'] .= "\xF0\x9F\x92\xB0 Цена: " . $product_info['price'] . $this->getConfig('currency');
        if (!is_null($product_info['dis_percent']) && !is_null($product_info['discount'])) {
            $message['text'] .= "\xE2\x9D\x97\xF0\x9F\x8E\x81 -" . $product_info['dis_percent'] . '%';
            if ($product_info['discount'] != 0) $message['text'] .= ' при покупке от ' . $product_info['discount'] . ' шт.';
        }
        $message['text'] .= "\r\n";
        if (!is_null($product_info['description'])) $message['text'] .= "\xF0\x9F\x93\x83 Описание: " . $product_info['description'] . "\r\n";

        if ($this->getConfig('type')) {
            if (is_null($quantity) || $quantity < 1) $quantity = 1;
            $message['reply_markup'] = new InlineKeyboard(
                [['text' => "\xF0\x9F\x9B\x92 Купить", 'callback_data' => 'c=so&id=' . $product_info['id'] . '&qu=' . $quantity]],
                [['text' => "\xE2\x9E\x96", 'callback_data' => 'c=op&id=' . $product_info['id'] . '&qu=' . ($quantity - 1) . '&cid=' . $product_info['cat_id'] . '&rp=' . $returnPage], ['text' => 'Количество: ' . $quantity . ' шт', 'callback_data' => 'c=null'], ['text' => "\xE2\x9E\x95", 'callback_data' => 'c=op&id=' . $product_info['id'] . '&qu=' . ($quantity + 1) . '&cid=' . $product_info['cat_id'] . '&rp=' . $returnPage]],
                [['text' => 'Свое количество', 'callback_data' => 'c=mq&id=' . $product_info['id'] . '&cid=' . $product_info['cat_id'] . '&rp=' . $returnPage]],
                [['text' => 'Назад', 'callback_data' => 'c=oc&id=' . $product_info['cat_id'] . '&np=' . $returnPage]]
            );
        } else {
            $quantity = Cart::getPositionQuantity($this->getCallbackQuery()->getFrom()->getId(), $product_info['id']);
            if (is_null($quantity)) {
                $message['reply_markup'] = new InlineKeyboard(
                    [['text' => "\xF0\x9F\x9B\x92 Добавить в корзину", 'callback_data' => 'c=ac&id=' . $product_info['id'] . '&rp=' . $returnPage . '&cid=' . $product_info['cat_id']]],
                    [['text' => 'Назад', 'callback_data' => 'c=oc&id=' . $product_info['cat_id'] . '&np=' . $returnPage]]
                );
            } else {
                $message['reply_markup'] = new InlineKeyboard(
                    [['text' => "\xF0\x9F\x9B\x92 Перейти в корзину", 'callback_data' => 'c=tc']],
                    [['text' => "\xE2\x9E\x96", 'callback_data' => 'c=dc&id=' . $product_info['id'] . '&cid=' . $product_info['cat_id'] . '&rp=' . $returnPage], ['text' => 'В корзине: ' . $quantity . ' шт', 'callback_data' => 'c=null'], ['text' => "\xE2\x9E\x95", 'callback_data' => 'c=ac&id=' . $product_info['id'] . '&cid=' . $product_info['cat_id'] . '&rp=' . $returnPage]],
                    [['text' => 'Назад', 'callback_data' => 'c=oc&id=' . $product_info['cat_id'] . '&np=' . $returnPage]]
                );
            }
        }
        return $message;
    }
}
