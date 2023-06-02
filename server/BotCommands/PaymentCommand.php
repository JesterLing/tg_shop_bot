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

use AdminPanel\Model\Users;
use AdminPanel\Model\Purchases;
use AdminPanel\Model\Cart;
use AdminPanel\Model\Products;

use AdminPanel\Payments\Crystalpay;
use AdminPanel\Payments\Qiwi;

/**
 * Пополнение баланса или оплата заказа
 */
class PaymentCommand extends SystemCommand
{

    protected $name = 'payment';
    protected $description = 'Оплата';
    protected $usage = '/payment';
    protected $version = '1.0.0';
    protected $conversation;

    public function execute(): ServerResponse
    {
        if ($this->getCallbackQuery() !== null) {
            $message = $this->getCallbackQuery()->getMessage();
            $user_id = $this->getCallbackQuery()->getFrom()->getId();
        } else {
            $message = $this->getMessage();
            $user_id = $message->getFrom()->getId();
        }

        $chat    = $message->getChat();
        $text    = trim($message->getText(true));
        $chat_id = $chat->getId();

        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];

        if (empty($notes['state'])) {
            $notes['state'] = 0;
        }

        $result = Request::emptyResponse();

        if ($text == 'Отмена') {
            if ($notes['state'] == 5) {
                if (isset($notes['order_id'])) {
                    Purchases::deleteByID($notes['order_id']);
                }
                if (isset($notes['payment_id'])) {
                    if ($notes['payment_service'] == 1) {
                        $payment = new Crystalpay($this->getConfig('crystalpay_login'), $this->getConfig('crystalpay_key'));
                        $payment->cancelPayment($notes['payment_id']);
                    }
                    if ($notes['payment_service'] == 2) {
                        $payment = new Qiwi($this->getConfig('qiwi_private_key'));
                        $payment->cancelPayment($notes['payment_id']);
                    }
                }
            }
            $this->telegram->executeCommand('cancel');
            return $result;
        }

        switch ($notes['state']) {
            case 0:

                if ($message->getContact() !== null) {

                    $notes['phone'] = $message->getContact()->getPhoneNumber();
                } elseif ($text !== '' && preg_match("/^\+380\d{3}\d{2}\d{2}\d{2}$/", $text)) {

                    $notes['phone'] = $text;
                    $text = '';
                } else {

                    $notes['state'] = 0;
                    $this->conversation->update();
                    Request::editMessageReplyMarkup([
                        'chat_id'    => $chat_id,
                        'message_id' => $message->getMessageId(),
                        'reply_markup' => ['inline_keyboard' => []]
                    ]);
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
                if ($text == 'По адресу') {
                    $notes['delivery'] = 1;
                } elseif ($text == 'Самовывоз') {
                    $notes['delivery'] = 0;
                } else {
                    $notes['state'] = 1;
                    $this->conversation->update();

                    $data = [
                        'text' => "Как доставить товар?\nДоставка на адрес доплачивается по тарифам такси",
                        'chat_id'      => $chat_id,
                        'reply_markup' => (new Keyboard(['По адресу', 'Самовывоз'], ['Отмена']))->setResizeKeyboard(true)->setOneTimeKeyboard(true),
                    ];

                    $result = Request::sendMessage($data);
                    break;
                }
                $text = '';
            case 2:

                if ($notes['delivery']) {
                    if ($message->getLocation() !== null) {
                        $notes['address'] = $message->getLocation()->getLongitude() . ',' . $message->getLocation()->getLatitude();
                    } else if ($text !== '') {
                        $notes['address'] = $text;
                        $text = '';
                    } else {

                        $notes['state'] = 2;
                        $this->conversation->update();

                        $data = [
                            'text' => 'Введите адрес для доставки или поделитесь своим текущим местоположением:',
                            'chat_id'      => $chat_id,
                            'reply_markup' => (new Keyboard((new KeyboardButton('Поделится местоположением'))->setRequestLocation(true), ['Отмена']))->setResizeKeyboard(true)->setOneTimeKeyboard(true),
                        ];

                        $result = Request::sendMessage($data);
                        break;
                    }
                }

            case 3:

                if (isset($notes['replenishment'])) {
                    if (is_numeric($text)) {
                        $notes['sum'] = $text;
                        $text = '';
                    } else {
                        $data = [
                            'text' => 'Введите сумму для поплнения в ' . $this->getConfig('currency') . ':',
                            'chat_id'      => $chat_id,
                            'reply_markup' => (new Keyboard(['Отмена']))->setResizeKeyboard(true)->setOneTimeKeyboard(true),
                        ];

                        $result = Request::sendMessage($data);
                        break;
                    }
                }

            case 4:

                $payment_options = [];
                if (!$this->getConfig('type') && $this->getConfig('pay_delivery')) $payment_options[] = 'При получении';
                if ($this->getConfig('type') && $this->getConfig('pay_balance') && !isset($notes['replenishment'])) $payment_options[] = 'Баланс бота';
                if ($this->getConfig('pay_crystalpay')) $payment_options[] = 'CRYSTALPAY BTC/ETH/BCH/Dash/Litecoin';
                if ($this->getConfig('pay_qiwi')) $payment_options[] = 'QIWI';
                if ($this->getConfig('pay_coinbase')) $payment_options[] = 'COINBASE BTC/ETH/USDT/LTC/BCH/DOGE';

                if (empty($payment_options)) {
                    $result = Request::sendMessage([
                        'text' => "Похоже что ни один метод оплаты не доступен. Свяжитесь с администратором бота.",
                        'chat_id'      => $chat_id,
                        'reply_markup' => (new Keyboard(['Отмена']))->setResizeKeyboard(true)->setOneTimeKeyboard(true),
                    ]);
                    return $result;
                }

                if (in_array($text, $payment_options, true)) {
                    switch ($text) {
                        case 'При получении':
                            $notes['payment_service'] = 0;
                            break;
                        case 'Баланс бота':
                            $notes['payment_service'] = 1;
                            break;
                        case 'CRYSTALPAY BTC/ETH/BCH/Dash/Litecoin':
                            $notes['payment_service'] = 2;
                            break;
                        case 'QIWI':
                            $notes['payment_service'] = 3;
                            break;
                        case 'COINBASE BTC/ETH/USDT/LTC/BCH/DOGE':
                            $notes['payment_service'] = 4;
                            break;
                    }
                } else {
                    $notes['state'] = 4;
                    $this->conversation->update();

                    $kb = new Keyboard([]);
                    foreach ($payment_options as $option) $kb->addRow(new KeyboardButton($option));
                    $kb->addRow(new KeyboardButton('Отмена'));
                    $data = [
                        'text' => "Оплата",
                        'chat_id'      => $chat_id,
                        'reply_markup' => $kb->setResizeKeyboard(true)->setOneTimeKeyboard(false),
                    ];

                    $result = Request::sendMessage($data);
                    break;
                }

            case 5:

                $notes['state'] = 5;
                if (!isset($notes['replenishment'])) {
                    if ($this->getConfig('type')) {

                        $product = Products::getByID($notes['id']);
                        $discount = 0;
                        if (!is_null($product['dis_percent']) && !is_null($product['discount'])) {
                            if ($notes['qu'] >= $product['discount']) {
                                $discount = round(($product['price'] / 100) * $product['dis_percent']);
                            }
                        }
                        $amount = ($product['price'] - $discount) * $notes['qu'];
                        $order_id = Purchases::addNew($user_id, $amount);
                    } else {
                        $amount = Cart::getCartSum($user_id);
                        $order_id = Purchases::addNew($user_id, $amount, $notes['delivery'] ?? null, $notes['phone'], $notes['delivery'] ?? null);
                    }
                    $notes['order_id'] = $order_id;
                    $sum = $amount;
                } else {
                    $sum = $notes['sum'];
                }
                $this->conversation->update();
                if ($notes['payment_service'] == 0) {
                    $this->conversation->stop();
                    Purchases::fillWithPositionsReal($user_id, $order_id);
                    OrdersCommand::giveOrderReal($this->getConfig('success_order'), $user_id);
                    return Request::emptyResponse();
                }

                if ($notes['payment_service'] == 1) {
                    if (Users::getBalance($user_id) >= $sum) {
                        Users::withdrawBalance($user_id, $sum);
                        Purchases::fillWithPositionsDigital($notes['order_id'], $notes['id'], $notes['qu']);
                        OrdersCommand::giveOrderDigital($notes['order_id'], $user_id);
                        $this->conversation->stop();
                        return Request::emptyResponse();
                    } else {
                        $data = [
                            'text' => 'Недостаточно средств на балансе. Выберите другой способ оплаты или пополните баланс',
                            'chat_id' => $chat_id,
                        ];
                    }
                }

                if ($notes['payment_service'] == 2) {
                    Request::sendMessage([
                        'text' => "Формируем платеж...",
                        'chat_id'      => $chat_id,
                        'reply_markup' => (new Keyboard(new KeyboardButton('Отмена')))->setResizeKeyboard(true)->setOneTimeKeyboard(true),
                    ]);
                    $payment = new Crystalpay($this->getConfig('crystalpay_login'), $this->getConfig('crystalpay_key'));
                    $payment->createPayment($user_id, $sum);
                    $notes['payment_id'] = $payment->getPaymentId();
                    $data = [
                        'text' => "Оплата Crystalpay. Бот проверяет оплату каждую минуту, если вы уверены что оплатили и не хотите ждать нажмите <i>Проверить оплату</i>.\nНеобходимо оплатить до <i>" . $payment->getExpiresAt() . "</i>",
                        'chat_id'      => $chat_id,
                        'parse_mode' => 'html',
                        'reply_markup' => new InlineKeyboard(
                            [['text' => "\xF0\x9F\x92\xB3 Перейти к оплате", 'url' => $payment->getUrl()]],
                            [['text' => "\xF0\x9F\x94\x84 Проверить оплату", 'callback_data' => 'c=cp']],
                        ),
                    ];
                }

                if ($notes['payment_service'] == 3) {
                    Request::sendMessage([
                        'text' => "Формируем платеж...",
                        'chat_id'      => $chat_id,
                        'reply_markup' => (new Keyboard(new KeyboardButton('Отмена')))->setResizeKeyboard(true)->setOneTimeKeyboard(true),
                    ]);
                    if (isset($order['id'])) {
                        $comment = 'Заказ ' . $order['id'] . ' в @' . $this->getConfig('bot_username');
                    } else {
                        $comment = 'Пополнение в @' . $this->getConfig('bot_username');
                    }
                    $payment = new Qiwi($this->getConfig('qiwi_private_key'));
                    $payment->createPayment($user_id, $sum, $comment, $order['id'] ?? null);
                    $notes['payment_id'] = $payment->getPaymentId();
                    $data = [
                        'text' => "Оплата на QIWI. Бот проверяет оплату каждую минуту, если вы уверены что оплатили и не хотите ждать нажмите <i>Проверить оплату</i>.\nНеобходимо оплатить до <i>" . $payment->getExpiresAt() . "</i>",
                        'chat_id'      => $chat_id,
                        'parse_mode' => 'html',
                        'reply_markup' => new InlineKeyboard(
                            [['text' => "\xF0\x9F\x92\xB3 Перейти к оплате", 'url' => $payment->getUrl()]],
                            [['text' => "\xF0\x9F\x94\x84 Проверить оплату", 'callback_data' => 'c=cp']],
                        ),
                    ];
                }

                if ($notes['payment_service'] == 4) {
                    Request::sendMessage([
                        'text' => "Формируем платеж...",
                        'chat_id'      => $chat_id,
                        'reply_markup' => (new Keyboard(new KeyboardButton('Отмена')))->setResizeKeyboard(true)->setOneTimeKeyboard(true),
                    ]);
                    $data = [
                        'text' => "Перейдите по ссыле ниже лол",
                        'chat_id'      => $chat_id,
                        'reply_markup' => new InlineKeyboard(
                            [['text' => "\xF0\x9F\x92\xB3 Перейти к оплате", 'url' => $payment['url']]],
                            [['text' => "\xF0\x9F\x94\x84 Проверить оплату", 'callback_data' => 'c=cp']],
                        ),
                    ];
                }
                $this->conversation->update();
                $result = Request::sendMessage($data);
                break;
        }

        return $result;
    }
}
