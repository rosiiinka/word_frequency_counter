<?php


class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = self::createNewConnection();
        }
        return self::$connection;
    }

    public static function reset(): PDO
    {
        $pdo = new PDO('sqlite:' . __DIR__ . '/words.db');
        $pdo->exec('DELETE FROM word_counts;');
        return $pdo;
    }

    private static function createNewConnection(): PDO
    {
        $pdo = new PDO('sqlite:' . __DIR__ . '/words.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE IF NOT EXISTS word_counts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            word TEXT UNIQUE,
            count INTEGER NOT NULL DEFAULT 0
        )');
        return $pdo;
    }
}
