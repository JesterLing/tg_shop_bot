<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;

use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;

use Longman\TelegramBot\DB;
use PDO;

class AdminCommand extends SystemCommand
{

    protected $name = 'admin';
    protected $description = 'Админка';
    protected $usage = '/admin';
    protected $version = '1.0.0';
    protected $conversation;

    public function execute(): ServerResponse
    {

        $user_id = $this->getMessage()->getFrom()->getId();
        $chat_id =  $this->getMessage()->getChat()->getId();
        $message = $this->getMessage();
        $text = trim($message->getText(true));

        $keyboard = new Keyboard(
            ['Добавить/удалить табаки'],
            ['Все заказы'],
            ['Настройки ответов'],
        );
        $keyboard->setResizeKeyboard(true);

        if($this->getTelegram()->isAdmin($user_id)) {

            $data = [
                'chat_id'    => $chat_id,
                'parse_mode' => 'html',
            ];

            $this->conversation = new Conversation($user_id, $chat_id);

            $notes = &$this->conversation->notes;
            !is_array($notes) && $notes = [];
            $state = $notes['state'] ?? 0;

            $result = Request::emptyResponse();

            if ($this->conversation->exists() && isset($notes['admin_command'])) {
                switch ($notes['admin_command']) {
                    case 'admin_change_start':

                        $this->conversation->stop();

                        AdminCommand::setSetting('greetings', $text);

                        $data['text'] = 'Сохранено';
                        $data['reply_markup'] = $keyboard;
                        Request::sendMessage($data);

                    break;
                    case 'admin_change_succes':

                        $this->conversation->stop();

                        AdminCommand::setSetting('successorder', $text);

                        $data['text'] = 'Сохранено';
                        $data['reply_markup'] = $keyboard;
                        Request::sendMessage($data);

                    break;
                    case 'admin_addtb':

                        switch ($state) {
                            case 0:
                                $notes['name_m'] = $text;
                                $text = '';
                            case 1:
                                if ($text === '') {
                                    $notes['state'] = 1;
                                    $this->conversation->update();

                                    $data['text'] = 'Вкус:';
                                    $data['reply_markup'] = (new Keyboard(['Отмена']))->setResizeKeyboard(true);
                                    $result = Request::sendMessage($data);
                                    break;
                                }
                                $notes['name'] = $text;
                                $text = '';
                            case 2:
                                if ($text === '' || !is_numeric($text)) {
                                    $notes['state'] = 2;
                                    $this->conversation->update();

                                    $data['text'] = 'Цена:';
                                    $data['reply_markup'] = (new Keyboard(['Отмена']))->setResizeKeyboard(true);
                                    $result = Request::sendMessage($data);
                                    break;
                                }
                                $notes['price'] = $text;
                                $text = '';
                            case 3:
                                if ($message->getPhoto() !== null) {
                                    $notes['photo'] = $message->getPhoto()[0]->getFileId();
                                } else if($text === 'Без фото') {
                                    $notes['photo'] = null;
                                    $text = '';
                                } else {
                                    $notes['state'] = 3;
                                    $this->conversation->update();
                                    $data['text'] = "Фото табака <i>(необязательно)</i>:";
                                    $data['reply_markup'] = (new Keyboard(['Без фото'],['Отмена']))->setResizeKeyboard(true);
                                    $result = Request::sendMessage($data);
                                    break;
                                }
                            case 4:
                                if (is_numeric($text)) {
                                    $notes['discount'] = $text;
                                    $text = '';
                                } else if($text === 'Без Скидки') {
                                    $notes['discount'] = null;
                                    $text = '';
                                } else {
                                    $notes['state'] = 4;
                                    $this->conversation->update();

                                    $data['text'] = "Скидка на этот табак в % <i>(без знака %)</i>:\n<i>Например от введеной цены в ".$notes['price']." грн\n5% - ".($notes['price'] * (5 / 100))."грн 10% - ".($notes['price'] * (10 / 100))."грн 20% - ".($notes['price'] * (20 / 100))."грн 30% - ".($notes['price'] * (30 / 100))."грн 40% - ".($notes['price'] * (40 / 100))."грн и Т.Д.</i>";
                                    $data['reply_markup'] = (new Keyboard(['Без Скидки'],['Отмена']))->setResizeKeyboard(true);
                                    $result = Request::sendMessage($data);
                                    break;
                                }
                            case 5:
                                if($notes['discount'] != null) {
                                    if (is_numeric($text)) {
                                        $notes['percent'] = $text;
                                    } else {
                                        $notes['state'] = 5;
                                        $this->conversation->update();

                                        $data['text'] = "После скольки товаров применяется скидка:\n<i>Например\nN - скидка применяется на каждую единицу табака после покупки N и более</i>";
                                        $data['reply_markup'] = (new Keyboard(['Отмена']))->setResizeKeyboard(true);
                                        $result = Request::sendMessage($data);
                                        break;
                                    }
                                }
                            case 6:
                                $this->conversation->update();
                                unset($notes['state']);
                                unset($notes['admin_command']);

                                if($notes['photo'] != null) {
                                    $file = Request::getFile(['file_id' => $notes['photo']]);
                                    if ($file->isOk()) {
                                        $result = $file->getResult();
                                        $notes['photo'] = $result->get_file_path();
                                        Request::downloadFile($result);
                                    }
                                }

                                self::addProduct($notes);
                                $this->conversation->stop();
                                $data['text'] = 'Готово';
                                $data['reply_markup'] = $keyboard;
                                $result = Request::sendMessage($data);
                                
                            break;
                        }

                    break;
                    case 'admin_edit':

                        $data['text'] = 'Готово';
                        $data['reply_markup'] = (new Keyboard(['Добавить/удалить табаки'],['Все заказы'],['Настройки ответов'],))->setResizeKeyboard(true);

                        switch ($state) {
                            case 0:
                                if($text != '') {
                                    self::editProduct($notes['id'], 'name_m', $text);
                                    $this->conversation->stop();


                                    $result = Request::sendMessage($data);
                                }
                            break;
                            case 1:
                                if($text != '') {
                                    self::editProduct($notes['id'], 'title', $text);
                                    $this->conversation->stop();

                                    $result = Request::sendMessage($data);
                                }
                            break;
                            case 2:
                                if(is_numeric($text)) {
                                    self::editProduct($notes['id'], 'price', $text);
                                    $this->conversation->stop();

                                    $result = Request::sendMessage($data);
                                }
                            break;
                            case 3:
                                if ($message->getPhoto() !== null) {
                                    $file = Request::getFile(['file_id' => $message->getPhoto()[0]->getFileId()]);
                                    if ($file->isOk()) {
                                        $result = $file->getResult();
                                        $path = $result->get_file_path();
                                        Request::downloadFile($result);
                                        self::editProduct($notes['id'], 'photo', $path);
                                        $this->conversation->stop();
                                        $result = Request::sendMessage($data);
                                    }
                                } else if($text === 'Без фото') {
                                    self::editProduct($notes['id'], 'photo', null);
                                    $this->conversation->stop();
                                    $result = Request::sendMessage($data);
                                } 

                            break;
                            case 4:
                                if (is_numeric($text)) {
                                    self::editProduct($notes['id'], 'percent', $text);
                                    $this->conversation->stop();
                                    $result = Request::sendMessage($data);
                                } else if($text === 'Без Скидки') {
                                    self::editProduct($notes['id'], 'percent', null);
                                    self::editProduct($notes['id'], 'discount', null);
                                    $this->conversation->stop();
                                    $result = Request::sendMessage($data);
                                }
                            break;
                            case 5:
                                if (is_numeric($text)) {
                                    self::editProduct($notes['id'], 'discount', $text);
                                    $this->conversation->stop();
                                    $result = Request::sendMessage($data);
                                } else if($text === 'Без Скидки') {
                                    self::editProduct($notes['id'], 'percent', null);
                                    self::editProduct($notes['id'], 'discount', null);
                                    $this->conversation->stop();
                                    $result = Request::sendMessage($data);
                                }
                            break;
                        }
                    break;
                }

                return $result;
            }

            $lastin = self::getSetting('lastin');
            self::setSetting('lastin', date('d-m-Y H:i'));
            $message = "Админка.\nПоследний вход - ".$lastin."\nВыход - /exit";
 
        } else {
            $message = 'Вы не аккаунте администратора';
        }
        return $this->replyToChat($message, [
            'reply_markup' => $keyboard,
        ]);
    }

    public static function getSetting($name)
    {
        $stg = DB::getPdo()->query('SELECT * FROM settings WHERE name = \''.$name.'\'');
        if($stg->rowCount() < 1) return false;
        $result = $stg->fetch(PDO::FETCH_ASSOC);
        return $result['value'];
    }

    public static function setSetting($name, $value)
    {
        $stg = DB::getPdo()->query('UPDATE settings SET value = \''.$value.'\' WHERE name = \''.$name.'\'');
        if($stg->rowCount() < 1) return false;
        return true;
    }

    public static function editManufacturer($id, $new_name)
    {
        DB::getPdo()->prepare('UPDATE manufacturers SET name_m = ? WHERE id = ?')->execute([$id, $new_name]);
    }
    
   public static function addProduct(array $data)
    {
        $sth = DB::getPdo()->query('SELECT manufacturers.id FROM manufacturers WHERE manufacturers.name_m = \''.$data['name_m'].'\' AND manufacturers.deleted = 0');
        if($sth->rowCount() < 1) {
            $sth = DB::getPdo()->prepare("INSERT INTO manufacturers (name_m) VALUES (:name_m)")->execute([':name_m' => $data['name_m']]);
            $m_id = DB::getPdo()->lastInsertId();
        } else {
            $response = $sth->fetch(PDO::FETCH_ASSOC);
            $m_id = $response['id'];
        }

        $sth = DB::getPdo()->prepare('INSERT INTO products (manufacturer_id, title, price, photo, discount, percent) VALUES (?, ?, ?, ?, ?, ?)');
        $sth->bindParam(1, $m_id);
        $sth->bindParam(2, $data['name']);
        $sth->bindParam(3, $data['price']);
        $sth->bindParam(4, $data['photo']);
        $sth->bindParam(5, $data['discount']);
        $sth->bindParam(6, $data['percent']);
        $sth->execute();
    }

    public static function editProduct($id, $column_name, $new_value)
    {
        if(in_array($column_name, ['name_m', 'title', 'price', 'photo', 'discount', 'percent', 'active'])) {
            if($column_name == 'name_m') {
                $sth = DB::getPdo()->prepare('SELECT id FROM manufacturers WHERE name_m = ? AND deleted = 0');
                $sth->execute([$new_value]);
                $res = $sth->fetch(PDO::FETCH_ASSOC);
                if(!is_array($res))
                {
                    $sth = DB::getPdo()->prepare('INSERT INTO manufacturers (name_m) VALUES (?)')->execute([$new_value]);
                    $new_value = DB::getPdo()->lastInsertId();
                } else {
                    
                    $new_value = $res['id'];
                }
                $column_name = 'manufacturer_id';
            }
            $sth = DB::getPdo()->prepare('UPDATE products SET '.$column_name.' = :column_value WHERE id = :id');
            $sth->bindParam(':column_value', $new_value);
            $sth->bindParam(':id', $id);
            $sth->execute();
            DB::getPdo()->query('UPDATE manufacturers SET deleted = 1 WHERE NOT EXISTS (SELECT * FROM products WHERE manufacturers.id = products.manufacturer_id AND products.deleted = 0)');
        }
    }

    public static function deleteProduct($id)
    {
        DB::getPdo()->prepare('UPDATE products SET deleted = 1 WHERE id = ?')->execute([$id]);
        /* check for manufacturers with no products and 'delete' */
        DB::getPdo()->query('UPDATE manufacturers SET deleted = 1 WHERE NOT EXISTS (SELECT * FROM products WHERE manufacturers.id = products.manufacturer_id AND products.deleted = 0)');
    }
}
