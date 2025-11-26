<?php

namespace PHPRag\Services;

use PDO;
use PDOException;

class DataBaseService
{
    private PDO $pdo;

    public function __construct()
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            env('DB_DRIVER'),
            env('DB_HOST'),
            env('DB_PORT'),
            env('DB_DATABASE')
        );

        try {
            $this->pdo = new PDO($dsn, env('DB_USERNAME'), env('DB_PASSWORD'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Erro ao conectar no PostgreSQL: ' . $e->getMessage());
        }
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function lastInsertId(): int
    {
        return $this->pdo->lastInsertId();
    }

    public function exec(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function fetchValue(string $sql, array $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
