<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

use Longman\TelegramBot\DB;
use PDO;

use TelegramBot\InlineKeyboardPagination\Exceptions\InlineKeyboardPaginationException;
use TelegramBot\InlineKeyboardPagination\InlineKeyboardPagination;

class TobaccoCommand extends SystemCommand
{

    protected $name = 'tobacco';
    protected $description = 'Показать табаки';
    protected $usage = '/tobacco';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        if ($pagination = self::getInlineKeyboardManufacturers()) {
            $data['inline_keyboard'] = self::getPaginationManufacturers($pagination['items']);
            if(count($pagination['keyboard']) > 1) $data['inline_keyboard'][] = $pagination['keyboard'];
            return $this->replyToChat('Табаки', [
                'reply_markup' => $data,
            ]);
        } else {
            return $this->replyToChat('Нет доступных табаков для продажи');
        }
    }

    public static function getProduct($itemId)
    {
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
       if($manufacturer == '' || $manufacturer == null) {
            return DB::getPdo()->query('SELECT products.*, manufacturers.name_m FROM products, manufacturers WHERE products.manufacturer_id = manufacturers.id AND products.active = 1 AND products.deleted = 0')->fetchAll(PDO::FETCH_ASSOC);
       } else {
            return DB::getPdo()->query('SELECT products.*, manufacturers.name_m FROM products, manufacturers WHERE manufacturer_id = '.$manufacturer.' AND products.manufacturer_id = manufacturers.id AND products.active = 1 AND products.deleted = 0')->fetchAll(PDO::FETCH_ASSOC);
       }
    }

    public static function getAllManufacturers()
    {      
        return DB::getPdo()->query('SELECT manufacturers.id, manufacturers.name_m, COUNT(products.manufacturer_id) AS total FROM manufacturers LEFT JOIN products ON products.manufacturer_id = manufacturers.id AND products.deleted = 0 WHERE manufacturers.deleted = 0 AND products.active = 1 GROUP BY manufacturers.id')->fetchAll(PDO::FETCH_ASSOC);
    }



    public static function getPaginationTobacco(array $items, $page = 1, $mf_page = 1, $manufacturer = null)
    {
        $buttons = [];
        foreach ($items as $row) {
            $buttons[] = [['text' => $row['name_m'].' '.$row['title'].' '.$row['price'].' грн', 'callback_data' => 'command=open&id='.$row['id'].'&mf='.$manufacturer.'&tbPage='.$page.'&mfPage='.$mf_page]];
        }
        return $buttons;
    }
    public static function getInlineKeyboardProducts($page = 1, $manufacturer = null)
    {
        $products = self::getProducts($manufacturer);

        if (empty($products)) {
            return null;
        }
        $ikp = new InlineKeyboardPagination($products, 'tb', $page, 5);
        try {
            $pagination = $ikp->getPagination();
        } catch (InlineKeyboardPaginationException $e) {
            $pagination = $ikp->getPagination(1);
        }

        return $pagination;
    }



    public static function getPaginationManufacturers(array $items, $page = 1)
    {
        $buttons[] = [['text' => 'Показать все', 'callback_data' => 'command=tb&newPage=1&mf=&mfPage='.$page]];
        foreach ($items as $row) {
            $buttons[] = [['text' => $row['name_m'].' ('.$row['total'].')', 'callback_data' => 'command=tb&newPage=1&mf='.$row['id'].'&mfPage='.$page]];
        }
        return $buttons;
    }

    public static function getInlineKeyboardManufacturers($page = 1)
    {
        $mfs = self::getAllManufacturers();

        if (empty($mfs)) {
            return null;
        }
        $ikp = new InlineKeyboardPagination($mfs, 'mf', $page, 5);

        try {
            $pagination = $ikp->getPagination();
        } catch (InlineKeyboardPaginationException $e) {
            $pagination = $ikp->getPagination(1);
        }
        return $pagination;
    }

}
