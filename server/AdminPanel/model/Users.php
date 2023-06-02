<?php

namespace AdminPanel\Model;

use AdminPanel\Module\DB;
use \PDO;

class Users
{
    public static function getAll()
    {
        return DB::query(
            'SELECT 
                user.id, 
                user.first_name, 
                user.last_name, 
                user.username, 
                balance.balance, 
                user.language_code, 
                user.is_premium, 
                user.created_at, 
                user.updated_at,
                IF(admins.id IS NULL, 0, 1) as `is_admin`
            FROM 
                `user` 
            LEFT JOIN `admins` ON admins.user_id = user.id
            LEFT JOIN `balance` ON user.id = balance.user_id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getByID(int $user_id)
    {
        $sth = DB::prepare(
            'SELECT 
                user.id, 
                user.first_name, 
                user.last_name, 
                user.username, 
                balance.balance, 
                COUNT(orders.id) AS orders 
            FROM 
                `user` 
                LEFT JOIN `balance` ON balance.user_id = user.id 
                LEFT JOIN `orders` ON orders.user_id = user.id 
            WHERE 
                user.id = ? 
            GROUP BY 
                user.id, 
                balance.balance'
        );
        $sth->execute([$user_id]);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public static function getBalance(int $user_id): int
    {
        $sth = DB::prepare('SELECT `balance` FROM `balance` WHERE `user_id` = ?');
        $sth->execute([$user_id]);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        if (empty($result)) return 0;
        return $result['balance'];
    }

    public static function addBalance(int $user_id, int $sum)
    {
        $sth = DB::prepare('INSERT INTO `balance` (user_id, balance) VALUES (:user_id, :sum) ON DUPLICATE KEY UPDATE `balance` = IF(user_id = VALUES(user_id), VALUES(balance) + balance, balance)');
        $sth->bindParam(':sum', $sum, PDO::PARAM_INT);
        $sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $sth->execute();
    }

    public static function withdrawBalance(int $user_id, int $sum)
    {
        $sth = DB::prepare('UPDATE `balance` SET `balance` = balance - :sum WHERE `user_id` = :user_id');
        $sth->bindParam(':sum', $sum, PDO::PARAM_INT);
        $sth->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $sth->execute();
    }
}
