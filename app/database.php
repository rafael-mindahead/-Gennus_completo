<?php

declare(strict_types=1);

function database(): mysqli
{
    static $connection;
    if (!$connection) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = new mysqli(
            getenv('DB_HOST') ?: 'mysql',
            getenv('DB_USER') ?: 'root',
            getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : 'root',
            getenv('DB_NAME') ?: 'gennus_database',
            (int) (getenv('DB_PORT') ?: 3306)
        );
        $connection->set_charset('utf8mb4');
    }
    return $connection;
}

function query(string $sql, array $params = []): mysqli_stmt
{
    $stmt = database()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
