<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

use TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;

use AdminPanel\Model\Categories;
use AdminPanel\Model\Products;

/**
 * Отображение категорий, подкатегорий и товаров
 */
class CategoriesCommand extends UserCommand
{
    protected $name = 'categories';
    protected $description = 'Каталог';
    protected $usage = '/categories';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        if ($this->getCallbackQuery() !== null) {
            $params = InlineKeyboardPagination::getParametersFromCallbackData($this->getCallbackQuery()->getData());
            if (empty($params['np'])) $params['np'] = '1';
            if (empty($params['rp'])) $params['rp'] = '1';
            if (empty($params['id'])) {
                $ctlg = Categories::getAllRoot();
                usort($ctlg, fn (array $a, array $b): int => $a['order'] <=> $b['order']);
            } else {
                $catgs = Categories::getWithChildByID($params['id']);
                $key = array_search($params['id'], array_column($catgs, 'id'));
                if (isset($catgs[$key]['cname'])) {
                    $this->description = 'Категория: ' . $catgs[$key]['cname'];
                }
                unset($catgs[$key]);
                usort($catgs, fn (array $a, array $b): int => $a['order'] <=> $b['order']);

                $prods = Products::getByCtgID($params['id']);
                $ctlg = array_merge($catgs, $prods);
            }

            if (empty($ctlg)) {
                return $this->getCallbackQuery()->answer([
                    'text'       => 'Пусто',
                    'show_alert' => true,
                    'cache_time' => 0,
                ]);
            }

            if ($pagination = $this->getInlineKeyboardCatalog($ctlg, $params['np'])) {
                $list['inline_keyboard'] = $this->buildCatalog($pagination['items'], $params['np'], $params['rp']);
                if ($params['id'] != null) {
                    foreach ($pagination['keyboard'] as &$kb) {
                        $kb['callback_data'] .= '&id=' . $params['id'];
                    }
                }
                if (count($pagination['keyboard']) > 1) $list['inline_keyboard'][] = $pagination['keyboard'];

                return Request::editMessageText([
                    'chat_id' => $this->getCallbackQuery()->getMessage()->getChat()->getId(),
                    'message_id' =>  $this->getCallbackQuery()->getMessage()->getMessageId(),
                    'text'       => $this->description,
                    'reply_markup' => $list,
                ]);
            }
        } else {
            $ctlg = Categories::getAllRoot();
            if (empty($ctlg)) {
                return $this->replyToChat('Пусто');
            }
            if ($pagination = $this->getInlineKeyboardCatalog($ctlg)) {
                $data['inline_keyboard'] = $this->buildCatalog($pagination['items']);
                if (count($pagination['keyboard']) > 1) $data['inline_keyboard'][] = $pagination['keyboard'];

                return $this->replyToChat('Каталог', [
                    'reply_markup' => $data,
                ]);
            }
        }
    }

    private function buildCatalog(array $items, $new_page = 1, $return_page = 1)
    {
        $is_root = false;
        $is_set = false;
        $back_id = null;

        foreach ($items as $row) {
            if (isset($row['cname'])) {
                if ($row['total'] != 0) {
                    $buttons[] = [['text' => $row['cname'] . ' (' . $row['total'] . ')', 'callback_data' => 'c=oc&id=' . $row['id'] . '&rp=' . $new_page]];
                } else {
                    $buttons[] = [['text' => $row['cname'], 'callback_data' => 'c=oc&id=' . $row['id'] . '&rp=' . $new_page]];
                }
                if (!$is_set) {
                    if (is_null($row['parent_id'])) {
                        $is_root = true;
                        $is_set = true;
                    } else {
                        $is_root = false;
                        $is_set = true;
                        $res = Categories::getParentID($row['parent_id']);
                        $back_id = $res['parent_id'] ?? null;
                    }
                }
            }
            if (isset($row['pname'])) {
                $text = $row['pname'] . ' | ' . $row['price'] . ' ' . $this->getConfig('currency');
                if (!is_null($row['quantity'])) $text .= ' | Кол-во: ' . $row['quantity'] . ' шт.';
                if (!is_null($row['dis_percent'])) $text .= " \xF0\x9F\x8E\x81 -" . $row['dis_percent'] . "%";
                $buttons[] = [['text' => $text, 'callback_data' => 'c=op&id=' . $row['id'] . '&rp=' . $new_page]];
                if ($is_root || is_null($back_id) && !$is_set) {
                    $is_root = false;
                    $is_set = true;
                    $res = Categories::getParentID($row['cat_id']);
                    $back_id = $res['parent_id'];
                }
            }
        }

        if (!$is_root) {
            if ($back_id == null) $buttons[] = [['text' => 'Назад', 'callback_data' => 'c=oc&np=' . $return_page]];
            else $buttons[] = [['text' => 'Назад', 'callback_data' => 'c=oc&id=' . $back_id . '&np=' . $return_page]];
        }
        return $buttons;
    }

    private function getInlineKeyboardCatalog(array $items, $page = 1)
    {
        $ikp = new InlineKeyboardPagination($items, 'oc', $page, 5);
        $ikp->setCallbackDataFormat('c={COMMAND}&rp={OLD_PAGE}&np={NEW_PAGE}');
        try {
            $pagination = $ikp->getPagination();
        } catch (InlineKeyboardPaginationException $e) {
            $pagination = $ikp->getPagination(1);
        }
        return $pagination;
    }
}
