<?php

namespace AdminPanel\Model;

use AdminPanel\Module\DB;
use \PDO;

class Categories
{
    public static function getAll()
    {
        return DB::query('SELECT * FROM categories GROUP BY id')->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAllRoot()
    {
        return  DB::query(
            'SELECT 
                categories.id, 
                categories.cname, 
                categories.order, 
                (
                SELECT 
                    COUNT(*) 
                FROM 
                    products 
                WHERE 
                    products.cat_id = categories.id
                ) AS total 
            FROM 
                categories 
            WHERE 
                parent_id IS NULL 
                AND hide = 0'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getWithChildByID(int $id)
    {
        $sth = DB::prepare(
            'SELECT 
                categories.id, 
                categories.cname, 
                categories.order, 
                categories.parent_id, 
                (
                SELECT 
                    COUNT(*) 
                FROM 
                    products 
                WHERE 
                    products.cat_id = categories.id
                ) AS total 
            FROM 
                `categories` 
            WHERE 
                `parent_id` = ? 
                OR categories.id = ? 
                AND categories.hide = 0'
        );
        $sth->execute([$id, $id]);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getByID($id)
    {
        $sth = DB::query('SELECT * FROM categories WHERE `id` = ?')->fetchAll(PDO::FETCH_ASSOC);
        $sth->execute([$id]);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public static function getParentID(int $children_id)
    {
        $sth = DB::prepare('SELECT `parent_id` FROM `categories` WHERE `id` = ?');
        $sth->execute([$children_id]);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    private static function getMaxOrder(): int
    {
        $sth = DB::query('SELECT MAX(`order`) + 1 FROM `categories` WHERE `parent_id` IS NULL')->fetch();
        if (empty($sth)) return 0;
        else return $sth[0];
    }

    public static function addNew(array $category)
    {
        $sth = DB::prepare('INSERT INTO categories (`cname`, `hide`, `order`, `parent_id`) VALUES (?, ?, ?, ?)');
        $sth->bindValue(1, $category['name'], PDO::PARAM_STR);
        $sth->bindValue(2, $category['hide'], PDO::PARAM_BOOL);
        $sth->bindValue(3, self::getMaxOrder(), PDO::PARAM_INT);
        $sth->bindValue(4, NULL, PDO::PARAM_NULL);
        return $sth->execute();
    }

    public static function editByID(array $category)
    {
        $sth = DB::prepare('UPDATE categories SET `cname` = ?, `hide` = ? WHERE `id` = ?');
        $sth->bindValue(1, $category['name'], PDO::PARAM_STR);
        $sth->bindValue(2, $category['hide'], PDO::PARAM_BOOL);
        $sth->bindValue(3, $category['id'], PDO::PARAM_INT);
        return $sth->execute();
    }

    public static function editOrder(array $categories)
    {
        try {
            DB::getPdo()->beginTransaction();
            foreach ($categories as $category) {
                $sth = DB::getPdo()->prepare('UPDATE categories SET `order` = ?, `parent_id` = ? WHERE `id` = ?');
                $sth->bindValue(1, $category['order'], PDO::PARAM_INT);
                $sth->bindValue(2, $category['parent_id'], PDO::PARAM_INT);
                $sth->bindValue(3, $category['id'], PDO::PARAM_INT);
                $sth->execute();
            }
            DB::getPdo()->commit();
        } catch (\PDOException $ex) {
            DB::getPdo()->rollBack();
            throw $ex;
        }
    }

    public static function deleteByID(int $id)
    {
        $sth = DB::prepare('DELETE FROM `categories` WHERE `id` = ? OR `parent_id` = ?');
        return $sth->execute([$id, $id]);
    }
}
