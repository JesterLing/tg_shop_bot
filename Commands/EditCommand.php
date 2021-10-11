<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;

use Longman\TelegramBot\DB;
use PDO;

use TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;

class EditCommand extends SystemCommand
{

    protected $name = 'edit';
    protected $description = 'Редактировать табаки';
    protected $usage = '/edit';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $user_id = $this->getMessage()->getFrom()->getId();
        $chat_id =  $this->getMessage()->getChat()->getId();
        $text = trim($this->getMessage()->getText(true));

        if($this->getTelegram()->isAdmin($user_id)) {

            $data = [
                'chat_id'    => $chat_id,
            ];

            $keyboard = new Keyboard(
                ['Добавить/удалить табаки'],
                ['Все заказы'],
                ['Настройки ответов'],
            );
            $keyboard->setResizeKeyboard(true);


            if ($pagination = self::getInlineKeyboardManufacturers()) {
                $data['inline_keyboard'] = self::getPaginationManufacturers($pagination['items']);
                if(count($pagination['keyboard']) > 1) $data['inline_keyboard'][] = $pagination['keyboard'];                              
            } else {
                $data['inline_keyboard'] = self::getPaginationManufacturers([]);
            }
            return $this->replyToChat('Добавить/удалить табаки', [
                'reply_markup' => $data,
            ]);
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


    public static function getProduct($itemId)
    {
        /* if its coal */
        if($itemId == 1) return DB::getPdo()->query('SELECT * FROM  products WHERE products.id = 1')->fetch(PDO::FETCH_ASSOC);
       
       return DB::getPdo()->query('
        SELECT 
            products.*, manufacturers.name_m 
        FROM 
            products, manufacturers
        WHERE
            products.id = '.$itemId.' AND products.manufacturer_id = manufacturers.id
        ')->fetch(PDO::FETCH_ASSOC);
    }

    public static function getProducts($manufacturer)
    {
        return DB::getPdo()->query('SELECT products.*, manufacturers.name_m FROM products, manufacturers WHERE manufacturer_id = '.$manufacturer.' AND products.manufacturer_id = manufacturers.id AND products.deleted = 0')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllManufacturers()
    {      
        return DB::getPdo()->query('SELECT manufacturers.id, manufacturers.name_m, COUNT(products.manufacturer_id) AS total FROM manufacturers LEFT JOIN products ON products.manufacturer_id = manufacturers.id  AND products.deleted = 0 WHERE manufacturers.deleted = 0 GROUP BY manufacturers.id')->fetchAll(PDO::FETCH_ASSOC);
    }



    public static function getPaginationTobacco(array $items, $page = 1, $mf_page = 1, $manufacturer = null)
    {
        $buttons = [];
        foreach ($items as $row) {
            $buttons[] = [
                            ['text' => $row['name_m'].' '.$row['title'].' '.$row['price'].' грн', 'callback_data' => 'command=admin_open&id='.$row['id'].'&mfPage='.$mf_page.'&mf='.$manufacturer.'&tbPage='.$page ],
                        ];
        }
        return $buttons;
    }
    public static function getInlineKeyboardProducts($page = 1, $manufacturer = null)
    {
        $products = self::getProducts($manufacturer);

        if (empty($products)) {
            return null;
        }
        $ikp = new InlineKeyboardPagination($products, 'admin_tb', $page, 5);
        try {
            $pagination = $ikp->getPagination();
        } catch (InlineKeyboardPaginationException $e) {
            $pagination = $ikp->getPagination(1);
        }

        return $pagination;
    }



    public static function getPaginationManufacturers(array $items, $page = 1)
    {
        $buttons[] = [['text' => 'Добавить новый табак', 'callback_data' => 'command=admin_addtb']];
        $buttons[] = [['text' => 'Кокосовый уголь', 'callback_data' => 'command=admin_opencoal']];
        foreach ($items as $row) {
            $buttons[] = [['text' => $row['name_m'].' ('.$row['total'].')', 'callback_data' => 'command=admin_tb&newPage=1&mf='.$row['id'].'&mfPage='.$page]];
        }
        return $buttons;
    }

    public static function getInlineKeyboardManufacturers($page = 1)
    {
        $mfs = self::getAllManufacturers();

        if (empty($mfs)) {
            return null;
        }
        $ikp = new InlineKeyboardPagination($mfs, 'admin_mf', $page, 5);
        try {
            $pagination = $ikp->getPagination();
        } catch (InlineKeyboardPaginationException $e) {
            $pagination = $ikp->getPagination(1);
        }
        return $pagination;
    }

}
