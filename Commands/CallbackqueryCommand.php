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
        //$callback_query = $this->getCallbackQuery();
        //$user_id        = $callback_query->getFrom()->getId();
        //$query_id       = $callback_query->getId();
        //$query_data     = $callback_query->getData();

        $answer         = null;
        $callback_query = $this->getCallbackQuery();
        $callback_data  = $callback_query->getData();
        $chat_id        = $callback_query->getMessage()->getChat()->getId();
        $user_id        = $callback_query->getFrom()->getId();
        $message_id     = $callback_query->getMessage()->getMessageId();

        if($callback_data == 'null') return $callback_query->answer();

        parse_str($callback_data, $params);

        switch($params['command']) {
            case 'mf':      // Manufacturers
                $params = InlineKeyboardPagination::getParametersFromCallbackData($callback_data);

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text'       => 'Табаки',
                ];
                if ($pagination = TobaccoCommand::getInlineKeyboardManufacturers($params['newPage'])) {
                   
                    $data['reply_markup']['inline_keyboard'] = TobaccoCommand::getPaginationManufacturers($pagination['items'], $params['newPage']);
                    if(count($pagination['keyboard']) > 1) $data['inline_keyboard'][] = $pagination['keyboard'];
                }

                Request::editMessageText($data);

            break;
            case 'tb':      // Tobaccos
                $params = InlineKeyboardPagination::getParametersFromCallbackData($callback_data);

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text'       => 'Табаки',
                ];

                if(isset($params['mf'])) $mf = $params['mf'];
                else $mf = null;

                if ($pagination = TobaccoCommand::getInlineKeyboardProducts($params['newPage'], $mf)) {
                    $data['reply_markup']['inline_keyboard'] = TobaccoCommand::getPaginationTobacco($pagination['items'], $params['newPage'], $params['mfPage'], $mf);
                    foreach($pagination['keyboard'] as &$kb) {
                        $kb['callback_data'] .= '&mfPage='.$params['mfPage'];
                        if($mf != null) $kb['callback_data'] .= '&mf='.$mf;
                    }
                    if(count($pagination['keyboard']) > 1) $data['reply_markup']['inline_keyboard'][] = $pagination['keyboard'];
                    $data['reply_markup']['inline_keyboard'][] = [['text' => 'Назад', 'callback_data' => 'command=mf&newPage='.$params['mfPage']]];
                }


                Request::editMessageText($data);

            break;

            case 'open':    // Tobacco open

                $item = TobaccoCommand::getProduct($params['id']);
                if(empty($item)) break;

                $inline_keyboard = new InlineKeyboard(
                   [['text' => 'Купить', 'callback_data' => 'command=add&id='.$item['id']]],
                    [['text' => 'Назад', 'callback_data' => 'command=tb&mf='.$params['mf'].'&mfPage='.$params['mfPage'].'&newPage='.$params['tbPage']]],
                );
                $message = '';

                if($item['photo'] != null) {

                    //$image = '<a href="https://'.$_SERVER['HTTP_HOST'].':8443/Download/'.$item['photo'].'">&#8205;</a>';
                    $message .= '<a href="https://ha.jester.su/static/icons/favicon-192x192.png">&#8205;</a>';
                }
                $message .= '<b>'.$item['name_m'].' '.$item['title'].' '.$item['price'].' грн</b>';
                if($item['percent'] != null && $item['discount'] != null) {
                  $message .= "\n\xE2\x9D\x97 Скидка ".$item['percent']."% при покупке от ".$item['discount']." шт \xE2\x9D\x97";
                }
                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text'       => $message,
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => false,
                    'reply_markup' => $inline_keyboard,
                ];    
  
                    Request::editMessageText($data);

            break;

            case 'add': // Tobacco add to cart

                 CartCommand::addToCart($user_id, $params['id']);
                 return $callback_query->answer([
                    'text'       => 'Добавлено в корзину',
                    'show_alert' => true,
                    'cache_time' => 0,
                ]);

            break;

            case 'clear':   // Clear cart

                CartCommand::clearCart($user_id);

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text'       => 'Корзина пуста'
                ];

                Request::editMessageText($data);

            break;

            case 'order':   // Start order

                 $this->getTelegram()->executeCommand("order");
                
            break;

            case 'add_coal':   // + 1

                CartCommand::addToCart($user_id, 1);

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text'       => 'Угли',
                    'reply_markup' => CoalCommand::getInlineCoals($user_id),
                ];

                Request::editMessageText($data);
            break;

            case 'remove_coal':  // - 1

                if(!CartCommand::deleteFromCart($user_id, 1)) return $callback_query->answer();

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text'       => 'Угли',
                    'reply_markup' => CoalCommand::getInlineCoals($user_id),
                ];

                Request::editMessageText($data);
            break;


            case 'history':

                $params = InlineKeyboardPagination::getParametersFromCallbackData($callback_data);

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                ];

                if ($pagination = HistoryCommand::getInlineKeyboardHistory($user_id, $params['newPage'])) {
                        if(count($pagination['keyboard']) > 1) $data['reply_markup']['inline_keyboard'][] = $pagination['keyboard'];
                }

                $data['text'] = HistoryCommand::getPaginationHistory($pagination['items'], $params['newPage']);

                Request::editMessageText($data);

            break;

            case 'admin_change_start':

                $conversation = new Conversation($user_id, $chat_id, 'admin');
                $conversation->notes['admin_command'] = 'admin_change_start';
                $conversation->update();
       
                $data = [
                    'chat_id'    => $chat_id,
                    'text' => 'Новое приветствие:',
                    'reply_markup' => (new Keyboard(['Отмена']))->setResizeKeyboard(true),
                ];
                Request::sendMessage($data);

            break;

            case 'admin_change_succes':

                $conversation = new Conversation($user_id, $chat_id, 'admin');
                $conversation->notes['admin_command'] = 'admin_change_succes';
                $conversation->update();
       
                $data = [
                    'chat_id'    => $chat_id,
                    'text' => 'Новое сообщение после успешного заказа:',
                    'reply_markup' => (new Keyboard(['Отмена']))->setResizeKeyboard(true),
                ];
                Request::sendMessage($data);

            break;

            case 'admin_all_orders':

                $params = InlineKeyboardPagination::getParametersFromCallbackData($callback_data);

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                ];

                if ($pagination = AllordersCommand::getInlineKeyboardAllOrders($params['newPage'])) {
                    $data['reply_markup']['inline_keyboard'][] = $pagination['keyboard'];
                }

                $data['text'] = AllordersCommand::getPaginationAllOrders($pagination['items'], $params['newPage']);

                Request::editMessageText($data);

            break;
            case 'admin_mf':      // Manufacturers

                if(isset($params['action'])) {
                    if($params['action'] == 'del') {
                        AdminCommand::deleteProduct($params['id']);
                         $callback_query->answer([
                            'text'       => 'Табак был удален',
                            'show_alert' => true,
                            'cache_time' => 0,
                        ]);
                    }

                }

                $params = InlineKeyboardPagination::getParametersFromCallbackData($callback_data);

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text'       => 'Добавить/удалить табаки',
                ];

                if ($pagination = EditCommand::getInlineKeyboardManufacturers($params['newPage'])) {
                    $data['reply_markup']['inline_keyboard'] = EditCommand::getPaginationManufacturers($pagination['items'], $params['newPage']);
                    if(count($pagination['keyboard']) > 1) $data['reply_markup']['inline_keyboard'][] = $pagination['keyboard'];
                } else {
                    $data['reply_markup']['inline_keyboard'] = EditCommand::getPaginationManufacturers([]);
                }
                Request::editMessageText($data);

            break;
            case 'admin_tb':      // Tobaccos

                $params = InlineKeyboardPagination::getParametersFromCallbackData($callback_data);

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text'       => 'Добавить/удалить табаки',
                ];

                if ($pagination = EditCommand::getInlineKeyboardProducts($params['newPage'], $params['mf'])) {
                    $data['reply_markup']['inline_keyboard'] = EditCommand::getPaginationTobacco($pagination['items'], $params['newPage'], $params['mfPage'], $params['mf']);
                    foreach($pagination['keyboard'] as &$kb) {
                        $kb['callback_data'] .= '&mfPage='.$params['mfPage'];
                        $kb['callback_data'] .= '&mf='.$params['mf'];
                    }
                    if(count($pagination['keyboard']) > 1)  $data['reply_markup']['inline_keyboard'][] = $pagination['keyboard'];
                    $data['reply_markup']['inline_keyboard'][] = [['text' => 'Назад', 'callback_data' => 'command=admin_mf&newPage='.$params['mfPage']]];
                }


                Request::editMessageText($data);

            break;
            case 'admin_open':    //Admin tobacco open 

                if(isset($params['action'])) {
                    if($params['action'] == 'show') {
                        AdminCommand::editProduct($params['id'], 'active', 1);
                        unset($params['action']);
                    } else if($params['action'] == 'hide') {
                        AdminCommand::editProduct($params['id'], 'active', 0);
                        unset($params['action']);
                    }

                }

                $item = EditCommand::getProduct($params['id']);
                if(empty($item)) break;

                if($item['active']) {
                    $showhide = ['text' => "\xF0\x9F\x91\x93 Скрыть", 'callback_data' => 'command=admin_open&action=hide&id='.$params['id'].'&mf='.$params['mf'].'&mfPage='.$params['mfPage'].'&tbPage='.$params['tbPage']];
                } else {
                    $showhide = ['text' => "\xF0\x9F\x91\x80 Показать", 'callback_data' => 'command=admin_open&action=show&id='.$params['id'].'&mf='.$params['mf'].'&mfPage='.$params['mfPage'].'&tbPage='.$params['tbPage']];
                }

                $inline_keyboard = new InlineKeyboard(
                    [['text' => 'Производитель: '.$item['name_m'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=name_m&id='.$item['id']]],
                    [['text' => 'Вкус: '.$item['title'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=title&id='.$item['id']]],
                    [['text' => 'Цена: '.$item['price'].' грн', 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=price&id='.$item['id']]],
                    [['text' => 'Фото ', 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=photo&id='.$item['id']]],
                    [['text' => 'Скидка(%): '.$item['percent'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=percent&id='.$item['id']]],
                    [['text' => 'Скидка после(шт): '.$item['discount'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=discount&id='.$item['id']]],
                    [$showhide, ['text' => "\xf0\x9f\x97\x91 Удалить", 'callback_data' => 'command=admin_mf&action=del&id='.$params['id'].'&newPage=1']],
                    [['text' => 'Назад', 'callback_data' => 'command=admin_tb&mf='.$params['mf'].'&mfPage='.$params['mfPage'].'&newPage='.$params['tbPage']]
                ]);

                $data = [
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text'       => 'Редактирование позиции',
                    'reply_markup' => $inline_keyboard,
                ];

                Request::editMessageText($data);

            break;

            case 'admin_edit':

                Request::editMessageText([
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text' => 'Редактирование позиции',
                    'reply_markup' => new InlineKeyboard([[]]),
                ]);

                $conversation = new Conversation($user_id, $chat_id, 'admin');
                $conversation->notes['admin_command'] = 'admin_edit';
                $conversation->notes['id'] = $params['id'];

                switch($params['field']) {
                    case 'name_m':
                        $message = 'Новое название производителя:';
                        $keyboard = (new Keyboard(['Отмена']))->setResizeKeyboard(true);
                        $conversation->notes['state'] = 0;
                    break;
                    case 'title':
                        $message = 'Новое имя:';
                        $keyboard = (new Keyboard(['Отмена']))->setResizeKeyboard(true);
                        $conversation->notes['state'] = 1;
                    break;
                    case 'price':
                        $message = 'Новая цена:';
                        $keyboard = (new Keyboard(['Отмена']))->setResizeKeyboard(true);
                        $conversation->notes['state'] = 2;
                    break;
                    case 'photo':
                        $message = 'Новое фото табака:';
                        $keyboard = (new Keyboard(['Без фото'], ['Отмена']))->setResizeKeyboard(true);
                        $conversation->notes['state'] = 3;
                    break;
                    case 'percent':
                        $message = 'Новая скидка в (%):';
                        $keyboard = (new Keyboard(['Без скидки'], ['Отмена']))->setResizeKeyboard(true);
                        $conversation->notes['state'] = 4;
                    break;
                    case 'discount':
                        $message = 'Новое значение скидка после(шт):';
                        $keyboard = (new Keyboard(['Без скидки'], ['Отмена']))->setResizeKeyboard(true);
                        $conversation->notes['state'] = 5;
                    break;
                }

                $conversation->update();

                Request::sendMessage([
                    'chat_id'    => $chat_id,
                    'text' => $message,
                    'reply_markup' => $keyboard,
                ]);

            break;

            case 'admin_addtb':

                Request::editMessageText([
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text' => 'Добавить новый табак',
                    'reply_markup' => new InlineKeyboard([[]]),
                ]);

                $conversation = new Conversation($user_id, $chat_id, 'admin');
                $conversation->notes['admin_command'] = 'admin_addtb';
                $conversation->update();

                Request::sendMessage([
                    'chat_id'    => $chat_id,
                    'text' => 'Производитель табака:',
                    'reply_markup' => (new Keyboard(['Отмена']))->setResizeKeyboard(true),
                ]);

            break;

            case 'admin_opencoal':

                $item = EditCommand::getProduct(1);
                Request::editMessageText([
                    'chat_id'    => $chat_id,
                    'message_id' => $message_id,
                    'text' => 'Кокосовый уголь',
                    'reply_markup' => new InlineKeyboard(
                        [['text' => 'Название: '.$item['title'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=title&id=1']],
                        [['text' => 'Цена: '.$item['price'].' грн', 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=price&id=1']],
                        [['text' => 'Скидка(%): '.$item['percent'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=percent&id=1']],
                        [['text' => 'Скидка после(шт): '.$item['discount'], 'callback_data' => 'null'], ['text' => "\xE2\x9C\x8F Редактировать", 'callback_data' => 'command=admin_edit&field=discount&id=1']], [['text' => 'Назад', 'callback_data' => 'command=admin_mf&newPage=1']] ),
                ]);

            break;

        }

        // Call all registered callbacks.
        foreach (self::$callbacks as $callback) {
            $answer = $callback($callback_query);
        }

        return ($answer instanceof ServerResponse) ? $answer : $callback_query->answer();
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
