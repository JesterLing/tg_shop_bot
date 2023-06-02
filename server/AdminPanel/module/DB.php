<?php

namespace AdminPanel\Module;

use \PDO;
use \Exception;

class DB
{
    protected static $pdo;

    public static function initialize()
    {
        if (!defined('HOST') || !defined('USER') || !defined('PASSWORD') || !defined('DATABASE') || !defined('PORT') || !defined('CHARSET')) {
            throw new Exception('Не заданы параметры для подключения к БД (файл config.php)');
        }
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . CHARSET,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];
        self::$pdo = new PDO('mysql:host=' . HOST . ';dbname=' . DATABASE . ';charset=' . CHARSET . ';port=' . PORT, USER, PASSWORD, $options);
    }

    public static function __callStatic($method, $args)
    {
        if (!self::isDbConnected()) {
            self::initialize();
        }
        return call_user_func_array(array(self::$pdo, $method), $args);
    }

    public static function getPdo(): ?PDO
    {
        if (!self::isDbConnected()) {
            self::initialize();
        }
        return self::$pdo;
    }

    public static function isDbConnected(): bool
    {
        return self::$pdo !== null;
    }
}
