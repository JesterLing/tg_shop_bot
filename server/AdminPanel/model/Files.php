<?php

namespace AdminPanel\Model;

use AdminPanel\Module\DB;
use \PDO;

class Files
{
    public static function insertFile($name, $size, $path, $tg_id = null): int
    {
        $sth = DB::prepare(
            'INSERT INTO files (
                `name`, `file_id`, `size`, `path`, 
                `created_at`
            )
            VALUES 
                (:name, :id, :size, :path, :created)'
        );
        $sth->bindValue(':name', $name, PDO::PARAM_STR);
        $sth->bindValue(':id', $tg_id, PDO::PARAM_STR);
        $sth->bindValue(':size', $size, PDO::PARAM_STR);
        $sth->bindValue(':path', $path, PDO::PARAM_STR);
        $sth->bindValue(':created', date('Y-m-d H:i:s'), PDO::PARAM_STR);
        $sth->execute();
        return DB::lastInsertId();
    }

    public static function generateFilename(): string
    {
        return uniqid() . "-" . time();
    }

    public static function getFileByID(int $id)
    {
        $sth = DB::prepare('SELECT * FROM `files` WHERE `id` = ?');
        $sth->execute([$id]);
        $file = $sth->fetch(PDO::FETCH_ASSOC);
        if (empty($file)) throw new \InvalidArgumentException('Файл с ID ' . $id . ' не найден');
        return $file;
    }

    public static function getFilesByIDs(array $ids)
    {
        $in = str_repeat('?,', count($ids) - 1) . '?';
        $sth = DB::prepare('SELECT * FROM `files` WHERE `id` IN (' . $in . ')');
        $sth->execute($ids);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getFileByTGID(string $tg_file_id)
    {
        $sth = DB::prepare('SELECT * FROM `files` WHERE `file_id` = ?');
        $sth->execute([$tg_file_id]);
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public static function updateFileByID(int $id, string $tg_id)
    {
        $sth = DB::prepare('UPDATE `files` SET `file_id` = ? WHERE `id` = ?');
        return $sth->execute([$tg_id, $id]);
    }
}
