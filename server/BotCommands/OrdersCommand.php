<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

use TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;

use AdminPanel\Model\Purchases;
use AdminPanel\Model\Files;

/**
 * Создание, выдача и просмотр всех заказов
 */
class OrdersCommand extends SystemCommand
{
    protected $name = 'orders';
    protected $description = 'Заказы';
    protected $usage = '/orders';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        if ($this->getCallbackQuery() !== null) {
            $params = InlineKeyboardPagination::getParametersFromCallbackData($this->getCallbackQuery()->getData());
            $hst = Purchases::getWithPositionsByUserID($this->getCallbackQuery()->getFrom()->getId());
            $data = [
                'chat_id'    => $this->getCallbackQuery()->getMessage()->getChat()->getId(),
                'message_id' => $this->getCallbackQuery()->getMessage()->getMessageId(),
                'parse_mode' => 'html',
                'disable_web_page_preview' => true,
            ];
            if ($pagination = $this->getInlineKeyboardHistory($hst, $params['np'])) {
                if ($this->getConfig('type')) $data['reply_markup']['inline_keyboard'][][] = ['text' => "\xF0\x9F\x93\x84 Посмотреть товар повторно", 'callback_data' => 'c=go&id=' . $pagination['items'][0]['id']];
                if (count($pagination['keyboard']) > 1) $data['reply_markup']['inline_keyboard'][] = $pagination['keyboard'];
            }

            $data['text'] = $this->buildMessage($pagination['items']);
            return Request::editMessageText($data);
        } else {
            $hst = Purchases::getWithPositionsByUserID($this->getMessage()->getFrom()->getId());
            if (empty($hst)) {
                return $this->replyToChat("Заказов еще не было");
            }
            if ($pagination = $this->getInlineKeyboardHistory($hst)) {
                if ($this->getConfig('type')) $keyboard['inline_keyboard'][][] = ['text' => "\xF0\x9F\x93\x84 Посмотреть товар повторно", 'callback_data' => 'c=go&id=' . $pagination['items'][0]['id']];
                if (count($pagination['keyboard']) > 1) $keyboard['inline_keyboard'][] = $pagination['keyboard'];
                return $this->replyToChat($this->buildMessage($pagination['items']), [
                    'parse_mode' => 'html',
                    'disable_web_page_preview' => true,
                    'reply_markup' => $keyboard,
                ]);
            }
        }
    }

    private function buildMessage(array $items)
    {
        $message = "";
        foreach ($items as $row) {
            $message = "\xF0\x9F\x92\xA1 Заказ: #<code>" . $row['id'] . "</code>\n";
            $message .= "\xF0\x9F\x95\x90 Дата: " . (date('d.m.Y H:i', strtotime($row['created_at'])));
            if (!$this->getConfig('type')) {
                $message .= "\n\xF0\x9F\x9A\x9A Доставка: ";
                if ($row['delivery']) {
                    if (preg_match('/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/', $row['address'])) {
                        $message .= "\xF0\x9F\x93\x8D <a href=\"https://www.google.com/maps/search/?api=1&query=" . $row['address'] . "\">Геопозиция</a>";
                    } else {
                        $message .= "\xF0\x9F\x9A\x9A <a href=\"https://www.google.com/maps/search/?api=1&query=" . $row['address'] . "\">" . $row['address'] . "</a>";
                    }
                } else {
                    $message .= "\xF0\x9F\x91\xA3 Самовывоз";
                }
                $message .= "\n\xF0\x9F\x93\xB1 Номер: " . $row['number'];
            }

            $message .= "\n";

            $amount = 0;
            foreach ($row['positions'] as $pos) {
                $discount = 0;
                $message .= "\n" . $pos['product'] . ' | ' . $pos['price'] . ' ' . $this->getConfig('currency') . '. | x' . $pos['quantity'] . ' шт.';
                if (!is_null($pos['dis_percent']) && !is_null($pos['discount'])) {
                    if ($pos['quantity'] >= $pos['discount']) {
                        $discount = round(($pos['price'] / 100) * $pos['dis_percent']);
                        $message .= " \xE2\x9D\x97\xF0\x9F\x8E\x81 -" . $pos['dis_percent'] . '%';
                    }
                }
                $amount += ($pos['price'] - $discount) * $pos['quantity'];
                $message .= ' = ' . ($pos['price'] - $discount) * $pos['quantity'] . ' ' . $this->getConfig('currency') . ".";
            }
            $message .= "\n\n\xF0\x9F\x92\xB0 Итоговая сумма: " . $amount . ' ' . $this->getConfig('currency') . '.';
        }
        return $message;
    }

    private function getInlineKeyboardHistory(array $items, $page = 1)
    {
        $ikp = new InlineKeyboardPagination($items, 'hs', $page, 1);
        $ikp->setCallbackDataFormat('c={COMMAND}&rp={OLD_PAGE}&np={NEW_PAGE}');
        try {
            $pagination = $ikp->getPagination();
        } catch (InlineKeyboardPaginationException $e) {
            $pagination = $ikp->getPagination(1);
        }
        return $pagination;
    }

    public static function giveOrderReal($success_text, $user_id)
    {
        Request::sendMessage([
            'text' => $success_text,
            'chat_id'      => $user_id,
            'reply_markup' => StartCommand::getKeyboard(0),
        ]);
    }

    public static function giveOrderDigital($order_id, $user_id)
    {
        $message = '';
        $sth = \AdminPanel\Module\DB::getPdo()->prepare('SELECT `product`, `content_type`, `content` FROM `purchases` WHERE `order_id` = ?');
        $sth->execute([$order_id]);
        $products = $sth->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($products as $product) {
            if ($product['content'] !== '') {
                if ($product['content_type'] == 'FILE') {
                    $ids = explode(",", $product['content']);
                    $files = Files::getFilesByIDs($ids);
                    foreach ($files as $file) {
                        if (is_null($file['file_id'])) {
                            $doc = Request::encodeFile(dirname(dirname(__DIR__)) . '/public' . $file['path']);
                        } else {
                            $doc = $file['file_id'];
                        }
                        $result = Request::sendDocument([
                            'chat_id' => $user_id,
                            'document'   => $doc,
                            'caption' => $product['product'],
                            'reply_markup' => StartCommand::getKeyboard(1),
                        ]);
                        if ($result->isOk() && (is_null($file['file_id']))) {
                            Files::updateFileByID($file['id'], $result->getResult()->getDocument()->getFileId());
                        }
                    }
                } else {
                    $message .= $product['product'] . "\n";
                    $message .= $product['content'] . "\n";
                }
            }
        }
        if ($message !== '') {
            Request::sendMessage([
                'text' => $message,
                'chat_id'      => $user_id,
                'reply_markup' => StartCommand::getKeyboard(1),
            ]);
        }
    }
}
