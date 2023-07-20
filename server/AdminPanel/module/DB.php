<?php

namespace AdminPanel\Module;

use \PDO;
use \Exception;

class DB
{
    protected static $pdo;

    public static function initialize()
    {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $_ENV['DB_CHARSET'],
            PDO::ATTR_EMULATE_PREPARES   => false
        ];
        self::$pdo = new PDO('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=' . $_ENV['DB_CHARSET'] . ';port=' . $_ENV['DB_PORT'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], $options);
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
