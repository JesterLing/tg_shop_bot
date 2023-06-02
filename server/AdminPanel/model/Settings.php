<?php

namespace AdminPanel\Model;

use AdminPanel\Module\DB;
use \PDO;

class Settings
{
    public static function getAll()
    {
        return DB::query('SELECT * FROM  `config`')->fetch(PDO::FETCH_ASSOC);
    }

    public static function saveAll(array $config)
    {
        $sth = DB::prepare(
            'UPDATE 
                `config` 
            SET 
                `bot_username` = ?, 
                `api_key` = ?, 
                `type` = ?, 
                `greetings` = ?, 
                `success_order` = ?, 
                `service` = ?, 
                `currency` = ?, 
                `pay_delivery` = ?, 
                `pay_qiwi` = ?, 
                `pay_crystalpay` = ?, 
                `crystalpay_login` = ?, 
                `crystalpay_key` = ?, 
                `qiwi_private_key` = ?, 
                `pay_coinbase` = ?, 
                `coinbase_key` = ? 
            WHERE 
                `id` = 0'
        );
        $sth->bindValue(1, $config['bot_username'], PDO::PARAM_STR);
        $sth->bindValue(2, $config['api_key'], PDO::PARAM_STR);
        $sth->bindValue(3, $config['type'], PDO::PARAM_BOOL);
        $sth->bindValue(4, $config['greetings'], PDO::PARAM_STR);
        $sth->bindValue(5, $config['success_order'], PDO::PARAM_STR);
        $sth->bindValue(6, $config['service'], PDO::PARAM_BOOL);
        $sth->bindValue(7, $config['currency'], PDO::PARAM_INT);
        $sth->bindValue(8, $config['pay_delivery'], PDO::PARAM_BOOL);
        $sth->bindValue(9, $config['pay_qiwi'], PDO::PARAM_BOOL);
        $sth->bindValue(10, $config['pay_crystalpay'], PDO::PARAM_BOOL);
        $sth->bindValue(11, $config['crystalpay_login'], PDO::PARAM_STR);
        $sth->bindValue(12, $config['crystalpay_key'], PDO::PARAM_STR);
        $sth->bindValue(13, $config['qiwi_private_key'], PDO::PARAM_STR);
        $sth->bindValue(14, $config['pay_coinbase'], PDO::PARAM_BOOL);
        $sth->bindValue(15, $config['coinbase_key'], PDO::PARAM_STR);
        return $sth->execute();
    }

    public static function getOnly(array $params)
    {
        return DB::query('SELECT ' . implode(',', $params) . ' FROM `config`')->fetch(PDO::FETCH_ASSOC);
    }

    public static function saveOnly(array $params)
    {
        $sth = DB::prepare('UPDATE `config` SET ' . implode(',', array_map(function ($v) {
            return $v . ' = ?';
        }, array_keys($params))) . ' WHERE `id` = 0');
        return $sth->execute(array_values($params));
    }

    public static function changeCurrency(string $from, string $to, \AdminPanel\Module\Exchange $exchange)
    {
        $tables = [
            'products'        => ['primary_key' => 'id', 'field' => 'price'],
            'purchases'       => ['primary_key' => 'id', 'field' => 'price'],
            'orders'          => ['primary_key' => 'id', 'field' => 'sum'],
            'balance'         => ['primary_key' => 'id', 'field' => 'balance'],
        ];
        foreach ($tables as $table => $params) {
            $query_select = sprintf('SELECT `%1$s`.`%2$s`, `%1$s`.`%3$s` FROM `%1$s`', $table, $params['primary_key'], $params['field']);
            $query_update = sprintf('UPDATE `%1$s` SET `%3$s` = ? WHERE `%2$s` = ?', $table, $params['primary_key'], $params['field']);
            $data = DB::query($query_select)->fetchAll(PDO::FETCH_ASSOC);
            try {
                $sth = DB::getPdo()->prepare($query_update);
                DB::getPdo()->beginTransaction();
                foreach ($data as $row) {
                    $newValue = $exchange->exchange($from, $to, $row[$params['field']]);
                    $sth->bindValue(1, $newValue, PDO::PARAM_INT);
                    $sth->bindValue(2, $row[$params['primary_key']], PDO::PARAM_INT);
                    $sth->execute();
                }
                DB::getPdo()->commit();
            } catch (\Exception $ex) {
                DB::getPdo()->rollback();
                throw $ex;
            }
        }
    }

    public static function botModeConvert($mode)
    {
        if (is_bool($mode)) {
            if ($mode) return 'digital';
            else return 'real';
        }
        if (is_int($mode)) {
            if ($mode === 1) return 'digital';
            else if ($mode === 0) return 'real';
            else return 'real';
        }
        if (is_string($mode)) {
            if ($mode === 'real') return false;
            else if ($mode === 'digital') return true;
            else return false;
        }
    }
}
