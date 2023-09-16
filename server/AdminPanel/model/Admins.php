<?php

namespace AdminPanel\Model;

use AdminPanel\Module\DB;
use \PDO;

class Admins
{
    public static function getAll()
    {
        return DB::query(
            'SELECT 
                admins.user_id, 
                files.path, 
                UNIX_TIMESTAMP(admins.created_at) as `created_at` 
            FROM 
                `admins` 
            LEFT JOIN `files` ON photo_file_id = files.id'
        )->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getByID(int $id)
    {
        $sth = DB::prepare('SELECT `refresh_token` FROM `admins` WHERE `id` = ?');
        $sth->execute([$id]);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public static function getBySecret(string $secret)
    {
        $sth = DB::prepare(
            'SELECT 
                admins.id, 
                admins.secret, 
                admins.secret_expired, 
                admins.user_id, 
                user.username, 
                user.first_name, 
                user.last_name, 
                files.path 
            FROM 
                `admins` 
                LEFT JOIN `user` ON admins.user_id = user.id 
                LEFT JOIN `files` ON admins.photo_file_id = files.id 
            WHERE 
                admins.secret = ?'
        );
        $sth->execute([$secret]);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public static function addNew(int $user_id, int $photo_id = null)
    {
        $sth = DB::prepare('INSERT INTO `admins` (`user_id`, `photo_file_id`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?)');
        $sth->bindValue(1, $user_id, PDO::PARAM_STR);
        $sth->bindValue(2, $photo_id, PDO::PARAM_INT);
        $sth->bindValue(3, date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $sth->bindValue(4, date('Y-m-d H:i:s'), PDO::PARAM_STR);
        return $sth->execute();
    }

    public static function editByID(int $user_id, string $secret, string $expired)
    {
        $sth = DB::prepare('UPDATE `admins` SET `secret` = ?, `secret_expired` = ?, refresh_token = null WHERE `user_id` = ?');
        $sth->bindValue(1, $secret, PDO::PARAM_STR);
        $sth->bindValue(2, $expired, PDO::PARAM_STR);
        $sth->bindValue(3, $user_id, PDO::PARAM_INT);
        return $sth->execute();
    }

    public static function editRefreshByID(string $token, int $id)
    {
        $sth = DB::prepare(
            'UPDATE 
                `admins` 
            SET 
                `secret` = null, 
                `secret_expired` = null, 
                `refresh_token` = ?, 
                `updated_at` = ? 
            WHERE 
                `id` = ?
	    '
        );
        $sth->bindValue(1, $token, PDO::PARAM_STR);
        $sth->bindValue(2, date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $sth->bindValue(3, $id, PDO::PARAM_INT);
        return $sth->execute();
    }

    public static function delByID(int $user_id)
    {
        $sth = DB::prepare('DELETE FROM `admins` WHERE `user_id` = ?');
        $sth->execute([$user_id]);
        return $sth->execute();
    }

    public static function clear()
    {
        $sth = DB::prepare('TRUNCATE TABLE `admins`');
        return $sth->execute();
    }
}
