<?php
namespace App\Facades;

use App\Classes\Database;
use PDO;

class DB
{
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function lastInsertId(): string
    {
        return Database::getConnection()->lastInsertId();
    }
}
