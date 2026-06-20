<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

// Fabrica unica da conexao PDO, evitando multiplas conexoes por requisicao.
class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = require dirname(__DIR__, 2) . '/config/database.php';
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']);
        self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        self::$pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        return self::$pdo;
    }
}
