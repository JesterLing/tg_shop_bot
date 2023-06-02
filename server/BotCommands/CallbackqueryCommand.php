<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;

use TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;

use AdminPanel\Model\Cart;
use AdminPanel\Model\Users;

use AdminPanel\Module\PaymentStatus;

use AdminPanel\Payments\Crystalpay;
use AdminPanel\Payments\Qiwi;

/**
 * Callback query command
 */
class CallbackqueryCommand extends SystemCommand
{
    /**
     * @var callable[]
     */
    protected static $callbacks = [];

    /**
     * @var string
     */
    protected $name = 'callbackquery';

    /**
     * @var string
     */
    protected $description = 'Обработка callback query';

    /**
     * @var string
     */
    protected $version = '1.2.0';

    /**
     * Command execute method
     *
     * @return ServerResponse
     */
    public function execute(): ServerResponse
    {

        $answer         = null;
        $chat_id        = $this->getCallbackQuery()->getMessage()->getChat()->getId();
        $user_id        = $this->getCallbackQuery()->getFrom()->getId();
        $message_id     = $this->getCallbackQuery()->getMessage()->getMessageId();

        if ($this->getCallbackQuery()->getData() == 'null') return $this->getCallbackQuery()->answer();

        parse_str($this->getCallbackQuery()->getData(), $params);

        switch ($params['c']) {
            case 'oc':      // open category

                $this->telegram->executeCommand('categories');

                break;
            case 'op':      // open product

                $this->telegram->executeCommand('product');

                break;
            case 'mq':      // enter your product quantity

                $conversation = new Conversation($user_id, $chat_id, 'product');
                unset($params['c']);
                $conversation->notes = $params;
                $conversation->update();
                Request::editMessageText([
                    'chat_id' => $chat_id,
                    'message_id' => $message_id,
                    'text' => 'Введите свое количество:',
                    'reply_markup' => ['inline_keyboard' => []]
                ]);

                break;
            case 'ac':    // add to cart

                Cart::addPosition($user_id, $params['id']);
                $quantity = Cart::getPositionQuantity($user_id, $params['id']);

                Request::editMessageReplyMarkup([
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'reply_markup' => new InlineKeyboard(
                        [['text' => "\xF0\x9F\x9B\x92 Перейти в корзину", 'callback_data' => 'c=tc']],
                        [['text' => "\xE2\x9E\x96", 'callback_data' => 'c=dc&id=' . $params['id'] . '&cid=' . $params['cid'] . '&rp=' . $params['rp']], ['text' => 'В корзине: ' . $quantity . ' шт', 'callback_data' => 'c=null'], ['text' => "\xE2\x9E\x95", 'callback_data' => 'c=ac&id=' . $params['id'] . '&cid=' . $params['cid'] . '&rp=' . $params['rp']]],
                        [['text' => 'Назад', 'callback_data' => 'c=oc&id=' . $params['cid'] . '&np=' . $params['rp']]]
                    )
                ]);

                break;
            case 'dc': // delete from cart

                Cart::delPosition($user_id, $params['id']);
                $quantity = Cart::getPositionQuantity($user_id, $params['id']);
                if (empty($quantity)) {
                    Request::editMessageReplyMarkup([
                        'chat_id'    => $chat_id,
                        'message_id' => $message_id,
                        'reply_markup' => new InlineKeyboard(
                            [['text' => "\xF0\x9F\x9B\x92 Добавить в корзину", 'callback_data' => 'c=ac&id=' . $params['id']]],
                            [['text' => 'Назад', 'callback_data' => 'c=oc&id=' . $params['cid'] . '&np=' . $params['rp']]]
                        )
                    ]);
                } else {
                    Request::editMessageReplyMarkup([
                        'chat_id'    => $chat_id,
                        'message_id' => $message_id,
                        'reply_markup' => new InlineKeyboard(
                            [['text' => "\xF0\x9F\x9B\x92 Перейти в корзину", 'callback_data' => 'c=tc']],
                            [['text' => "\xE2\x9E\x96", 'callback_data' => 'c=dc&id=' . $params['id'] . '&cid=' . $params['cid'] . '&rp=' . $params['rp']], ['text' => 'В корзине: ' . $quantity . ' шт', 'callback_data' => 'c=null'], ['text' => "\xE2\x9E\x95", 'callback_data' => 'c=ac&id=' . $params['id'] . '&cid=' . $params['cid'] . '&rp=' . $params['rp']]],
                            [['text' => 'Назад', 'callback_data' => 'c=oc&id=' . $params['cid'] . '&np=' . $params['rp']]]
                        )
                    ]);
                }
                break;
            case 'cc':   // clear cart

                Cart::clearAll($user_id);
                Request::editMessageText([
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text'       => 'Корзина пуста'
                ]);

                break;
            case 'tc':   // open cart

                $this->telegram->executeCommand('cart');

                break;
            case 'ec':   // edit cart

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                ];
                if ($params['ac'] == 'del') {
                    $quantity = Cart::getPositionQuantity($user_id, $params['id']);
                    Cart::delPosition($user_id, $params['id'], $quantity);
                    $params['np'] = $params['rp'];
                }
                if ($params['ac'] == 'pls') {
                    Cart::addPosition($user_id, $params['id']);
                }
                if ($params['ac'] == 'min') {
                    Cart::delPosition($user_id, $params['id']);
                    $quantity = Cart::getPositionQuantity($user_id, $params['id']);
                    if (is_null($quantity)) $params['np'] = $params['rp'];
                }
                $cart = Cart::getAllByUserID($user_id);
                if (empty($cart)) {
                    $data['text'] = 'Корзина пуста';
                    Request::editMessageText($data);
                    break;
                }
                if (empty($params['np'])) $params['np'] = 1;
                if ($pagination = CartCommand::getInlineKeyboardEditCart($cart, $params['np'])) {
                    $data['text'] = CartCommand::buildEditCart($pagination['items'][0], 'грн');
                    $quantity = Cart::getPositionQuantity($user_id, $pagination['items'][0]['id']);
                    $data['reply_markup']['inline_keyboard'] = [
                        [
                            ['text' => "\xE2\x9E\x96", 'callback_data' => 'c=ec&ac=min&id=' . $pagination['items'][0]['id'] . '&np=' . $params['np']],
                            ['text' => "\xE2\x9D\x8C Убрать", 'callback_data' => 'c=ec&ac=del&id=' . $pagination['items'][0]['id'] . '&np=' . $params['np']],
                            ['text' => "\xE2\x9E\x95", 'callback_data' => 'c=ec&ac=pls&id=' . $pagination['items'][0]['id'] . '&np=' . $params['np']]
                        ],
                        [['text' => "\xF0\x9F\x94\x99 Назад в корзину", 'callback_data' => 'c=tc']]
                    ];

                    if (count($pagination['keyboard']) > 1) $data['reply_markup']['inline_keyboard'][] = $pagination['keyboard'];
                    Request::editMessageText($data);
                }

                break;
            case 'so':   // start clearance

                if ($this->getConfig('type')) {
                    $conversation = new Conversation($user_id, $chat_id, 'payment');
                    $conversation->notes['state'] = 4;
                    unset($params['c']);
                    $conversation->notes = array_merge($conversation->notes, $params);
                    $conversation->update();
                }
                $this->telegram->executeCommand('payment');

                break;
            case 'ab':   // top up balance

                Request::editMessageReplyMarkup([
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'reply_markup' => ['inline_keyboard' => []]
                ]);
                $conversation = new Conversation($user_id, $chat_id, 'payment');
                $conversation->notes['state'] = 3;
                $conversation->notes['replenishment'] = true;
                $conversation->update();
                $this->telegram->executeCommand('payment');

                break;
            case 'go':   // give order

                OrdersCommand::giveOrderDigital($params['id'], $user_id);

                break;
            case 'cp':   // check payment

                $conversation = new Conversation($user_id, $chat_id);
                if ($conversation->getCommand() == 'payment') {
                    $result = false;

                    if ($conversation->notes['payment_service'] == 2) {
                        $payment = new Crystalpay($this->getConfig('crystalpay_login'), $this->getConfig('crystalpay_key'));
                        $result = $payment->checkPayment($conversation->notes['payment_id']);
                    }
                    if ($conversation->notes['payment_service'] == 3) {
                        $payment = new Qiwi($this->getConfig('qiwi_private_key'));
                        $result = $payment->checkPayment($conversation->notes['payment_id']);
                    }
                    if ($conversation->notes['payment_service'] == 4) {
                        //
                    }
                    if ($result == false) {
                        $this->getCallbackQuery()->answer([
                            'text'       => 'Между проверками должно пройти минимум 15 сек',
                            'show_alert' => true,
                            'cache_time' => 0,
                        ]);
                        break;
                    }
                    if ($result == PaymentStatus::PAID) {
                        Request::editMessageText([
                            'chat_id'    => $chat_id,
                            'message_id' => $message_id,
                            'text'       => 'Оплата получена',
                            'reply_markup' => ['inline_keyboard' => []]
                        ]);
                        if (isset($conversation->notes['replenishment'])) {
                            Users::addBalance($user_id, $conversation->notes['sum']);
                            Request::sendMessage([
                                'text' => 'Баланс пополнен на ' . $conversation->notes['sum'] . ' ' . $this->getConfig('currency'),
                                'chat_id'      => $user_id,
                                'reply_markup' => StartCommand::getKeyboard($this->getConfig('type')),
                            ]);
                        } else {
                            if ($this->getConfig('type')) {
                                OrdersCommand::giveOrderDigital($conversation->notes['order_id'], $user_id);
                            } else {
                                OrdersCommand::giveOrderReal($this->getConfig('success_order'), $user_id);
                            }
                        }
                        $conversation->stop();
                        break;
                    }
                    if ($result == PaymentStatus::PENDING) {
                        $this->getCallbackQuery()->answer([
                            'text'       => 'Похоже что оплата еще не поступила',
                            'show_alert' => true,
                            'cache_time' => 0,
                        ]);
                        break;
                    }
                    if ($result == PaymentStatus::REJECTED) {
                        $conversation->cancel();
                        Request::editMessageText([
                            'chat_id'    => $chat_id,
                            'message_id' => $message_id,
                            'text'       => 'Оплата была отклонена',
                            'reply_markup' => ['inline_keyboard' => []]
                        ]);
                    }
                    if ($result == PaymentStatus::EXPIRED) {
                        $conversation->stop();
                        Request::editMessageText([
                            'chat_id'    => $chat_id,
                            'message_id' => $message_id,
                            'text'       => 'Время для оплаты истекло',
                            'reply_markup' => ['inline_keyboard' => []]
                        ]);
                    }
                    if ($result == PaymentStatus::CANCELED) {
                        $conversation->cancel();
                        Request::editMessageText([
                            'chat_id'    => $chat_id,
                            'message_id' => $message_id,
                            'text'       => 'Оплата была отменена',
                            'reply_markup' => ['inline_keyboard' => []]
                        ]);
                    }
                    if ($result == PaymentStatus::UNKNOWN) {
                        $this->getCallbackQuery()->answer([
                            'text'       => 'Неизвестный ответ',
                            'show_alert' => true,
                            'cache_time' => 0,
                        ]);
                        break;
                    }
                    Request::sendMessage([
                        'text' => 'Возврат в главное меню',
                        'chat_id'      => $chat_id,
                        'reply_markup' => StartCommand::getKeyboard($this->getConfig('type')),
                    ]);
                }

                break;
            case 'hs':

                $this->telegram->executeCommand('orders');

                break;

            case 'ecdc':
                Cart::delPosition($user_id, $params['id']);
                $quantity = Cart::getPositionQuantity($user_id, $params['id']);
                if (empty($quantity)) {
                    $this->getCallbackQuery()->answer([
                        'text'       => 'Уже минимальное количество',
                        'show_alert' => true,
                        'cache_time' => 0,
                    ]);
                } else {
                    Request::editMessageReplyMarkup([
                        'chat_id'    => $chat_id,
                        'message_id' => $message_id,
                        'reply_markup' => new InlineKeyboard(
                            [
                                ['text' => "\xE2\x9E\x96", 'callback_data' => 'c=dc&id=' . $params['id'] . '&rp=' . $params['rp']],
                                ['text' => 'В корзине: ' . $quantity . ' шт', 'callback_data' => 'c=null'],
                                ['text' => "\xE2\x9E\x95", 'callback_data' => 'c=ac&id=' . $params['id'] . '&rp=' . $params['rp']]
                            ],
                            [['text' => "\xE2\x9D\x8C Убрать", 'callback_data' => 'c=ecdl&id=' . $params['id'] . '&rp=' . $params['rp']]],
                            [['text' => "\xF0\x9F\x94\x99 Назад в корзину", 'callback_data' => 'c=tc']]
                        )
                    ]);
                }

                break;

                // case 'admin_all_orders':

                //     $params = InlineKeyboardPagination::getParametersFromCallbackData($callback_data);

                //     $data = [
                //         'chat_id'    => $chat_id,
                //         'message_id' => $message_id,
                //         'parse_mode' => 'html',
                //         'disable_web_page_preview' => true,
                //     ];

                //     if ($pagination = AllordersCommand::getInlineKeyboardAllOrders($params['newPage'])) {
                //         $data['reply_markup']['inline_keyboard'][] = $pagination['keyboard'];
                //     }

                //     $data['text'] = AllordersCommand::getPaginationAllOrders($pagination['items'], $params['newPage']);

                //     Request::editMessageText($data);

                //     break;
                // case 'admin_mf':      // Manufacturers

                //     if (isset($params['action'])) {
                //         if ($params['action'] == 'del') {
                //             AdminCommand::deleteProduct($params['id']);
                //             $this->getCallbackQuery()->answer([
                //                 'text'       => 'Табак был удален',
                //                 'show_alert' => true,
                //                 'cache_time' => 0,
                //             ]);
                //         }
                //     }

                //     $params = InlineKeyboardPagination::getParametersFromCallbackData($callback_data);

                //     $data = [
                //         'chat_id'    => $chat_id,
                //         'message_id' => $message_id,
                //         'text'       => 'Добавить/удалить табаки',
                //     ];

                //     if ($pagination = EditCommand::getInlineKeyboardManufacturers($params['newPage'])) {
                //         $data['reply_markup']['inline_keyboard'] = EditCommand::getPaginationManufacturers($pagination['items'], $params['newPage']);
                //         if (count($pagination['keyboard']) > 1) $data['reply_markup']['inline_keyboard'][] = $pagination['keyboard'];
                //     } else {
                //         $data['reply_markup']['inline_keyboard'] = EditCommand::getPaginationManufacturers([]);
                //     }
                //     Request::editMessageText($data);

                //     break;
                // case 'admin_tb':      // Tobaccos

                //     $params = InlineKeyboardPagination::getParametersFromCallbackData($callback_data);

                //     $data = [
                //         'chat_id'    => $chat_id,
                //         'message_id' => $message_id,
                //         'text'       => 'Добавить/удалить табаки',
                //     ];

                //     if ($pagination = EditCommand::getInlineKeyboardProducts($params['newPage'], $params['mf'])) {
                //         $data['reply_markup']['inline_keyboard'] = EditCommand::getPaginationTobacco($pagination['items'], $params['newPage'], $params['mfPage'], $params['mf']);
                //         foreach ($pagination['keyboard'] as &$kb) {
                //             $kb['callback_data'] .= '&mfPage=' . $params['mfPage'];
                //             $kb['callback_data'] .= '&mf=' . $params['mf'];
                //         }
                //         if (count($pagination['keyboard']) > 1)  $data['reply_markup']['inline_keyboard'][] = $pagination['keyboard'];
                //         $data['reply_markup']['inline_keyboard'][] = [['text' => 'Назад', 'callback_data' => 'command=admin_mf&newPage=' . $params['mfPage']]];
                //     }


                //     Request::editMessageText($data);

                //     break;
                // case 'admin_open':    //Admin tobacco open 

                //     if (isset($params['action'])) {
                //         if ($params['action'] == 'show') {
                //             AdminCommand::editProduct($params['id'], 'active', 1);
                //             unset($params['action']);
                //         } else if ($params['action'] == 'hide') {
                //             AdminCommand::editProduct($params['id'], 'active', 0);
                //             unset($params['action']);
                //         }
                //     }

                //     $item = EditCommand::getProduct($params['id']);
                //     if (empty($item)) break;

                //     if ($item['active']) {
                //         $showhide = ['text' => "\xF0\x9F\x91\x93 Скрыть", 'callback_data' => 'command=admin_open&action=hide&id=' . $params['id'] . '&mf=' . $params['mf'] . '&mfPage=' . $params['mfPage'] . '&tbPage=' . $params['tbPage']];
                //     } else {
                //         $showhide = ['text' => "\xF0\x9F\x91\x80 Показать", 'callback_data' => 'command=admin_open&action=show&id=' . $params['id'] . '&mf=' . $params['mf'] . '&mfPage=' . $params['mfPage'] . '&tbPage=' . $params['tbPage']];
                //     }

                //     $inline_keyboard = new InlineKeyboard(
                //         [['text' => 'Производитель: ' . $item['name_m'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=name_m&id=' . $item['id']]],
                //         [['text' => 'Вкус: ' . $item['title'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=title&id=' . $item['id']]],
                //         [['text' => 'Цена: ' . $item['price'] . ' грн', 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=price&id=' . $item['id']]],
                //         [['text' => 'Фото ', 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=photo&id=' . $item['id']]],
                //         [['text' => 'Скидка(%): ' . $item['percent'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=percent&id=' . $item['id']]],
                //         [['text' => 'Скидка после(шт): ' . $item['discount'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=discount&id=' . $item['id']]],
                //         [$showhide, ['text' => "\xf0\x9f\x97\x91 Удалить", 'callback_data' => 'command=admin_mf&action=del&id=' . $params['id'] . '&newPage=1']],
                //         [
                //             ['text' => 'Назад', 'callback_data' => 'command=admin_tb&mf=' . $params['mf'] . '&mfPage=' . $params['mfPage'] . '&newPage=' . $params['tbPage']]
                //         ]
                //     );

                //     $data = [
                //         'chat_id'    => $chat_id,
                //         'message_id' => $message_id,
                //         'text'       => 'Редактирование позиции',
                //         'reply_markup' => $inline_keyboard,
                //     ];

                //     Request::editMessageText($data);

                //     break;

                // case 'admin_edit':

                //     Request::editMessageText([
                //         'chat_id'    => $chat_id,
                //         'message_id' => $message_id,
                //         'text' => 'Редактирование позиции',
                //         'reply_markup' => new InlineKeyboard([[]]),
                //     ]);

                //     $conversation = new Conversation($user_id, $chat_id, 'admin');
                //     $conversation->notes['admin_command'] = 'admin_edit';
                //     $conversation->notes['id'] = $params['id'];

                //     switch ($params['field']) {
                //         case 'name_m':
                //             $message = 'Новое название производителя:';
                //             $keyboard = (new Keyboard(['Отмена']))->setResizeKeyboard(true);
                //             $conversation->notes['state'] = 0;
                //             break;
                //         case 'title':
                //             $message = 'Новое имя:';
                //             $keyboard = (new Keyboard(['Отмена']))->setResizeKeyboard(true);
                //             $conversation->notes['state'] = 1;
                //             break;
                //         case 'price':
                //             $message = 'Новая цена:';
                //             $keyboard = (new Keyboard(['Отмена']))->setResizeKeyboard(true);
                //             $conversation->notes['state'] = 2;
                //             break;
                //         case 'photo':
                //             $message = 'Новое фото табака:';
                //             $keyboard = (new Keyboard(['Без фото'], ['Отмена']))->setResizeKeyboard(true);
                //             $conversation->notes['state'] = 3;
                //             break;
                //         case 'percent':
                //             $message = 'Новая скидка в (%):';
                //             $keyboard = (new Keyboard(['Без скидки'], ['Отмена']))->setResizeKeyboard(true);
                //             $conversation->notes['state'] = 4;
                //             break;
                //         case 'discount':
                //             $message = 'Новое значение скидка после(шт):';
                //             $keyboard = (new Keyboard(['Без скидки'], ['Отмена']))->setResizeKeyboard(true);
                //             $conversation->notes['state'] = 5;
                //             break;
                //     }

                //     $conversation->update();

                //     Request::sendMessage([
                //         'chat_id'    => $chat_id,
                //         'text' => $message,
                //         'reply_markup' => $keyboard,
                //     ]);

                //     break;

                // case 'admin_addtb':

                //     Request::editMessageText([
                //         'chat_id'    => $chat_id,
                //         'message_id' => $message_id,
                //         'text' => 'Добавить новый табак',
                //         'reply_markup' => new InlineKeyboard([[]]),
                //     ]);

                //     $conversation = new Conversation($user_id, $chat_id, 'admin');
                //     $conversation->notes['admin_command'] = 'admin_addtb';
                //     $conversation->update();

                //     Request::sendMessage([
                //         'chat_id'    => $chat_id,
                //         'text' => 'Производитель табака:',
                //         'reply_markup' => (new Keyboard(['Отмена']))->setResizeKeyboard(true),
                //     ]);

                //     break;

                // case 'admin_opencoal':

                //     $item = EditCommand::getProduct(1);
                //     Request::editMessageText([
                //         'chat_id'    => $chat_id,
                //         'message_id' => $message_id,
                //         'text' => 'Кокосовый уголь',
                //         'reply_markup' => new InlineKeyboard(
                //             [['text' => 'Название: ' . $item['title'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=title&id=1']],
                //             [['text' => 'Цена: ' . $item['price'] . ' грн', 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=price&id=1']],
                //             [['text' => 'Скидка(%): ' . $item['percent'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=percent&id=1']],
                //             [['text' => 'Скидка после(шт): ' . $item['discount'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=discount&id=1']],
                //             [['text' => 'Назад', 'callback_data' => 'command=admin_mf&newPage=1']]
                //         ),
                //     ]);

                //     break;
        }

        // Call all registered callbacks.
        foreach (self::$callbacks as $callback) {
            $answer = $callback($this->getCallbackQuery());
        }

        return ($answer instanceof ServerResponse) ? $answer : $this->getCallbackQuery()->answer();
    }

    /**
     * Add a new callback handler for callback queries.
     *
     * @param $callback
     */
    public static function addCallbackHandler($callback): void
    {
        self::$callbacks[] = $callback;
    }
}
