<?php

declare(strict_types=1);

namespace Xtompie\Dao;

use Exception;
use Generator;
use PDO;
use PDOStatement;

class PdoAdapter implements Adapter
{
    public function __construct(
        protected Pdo $pdo,
    ) {
    }

    public function query(string $query, array $binds): array
    {
        $stmt = $this->stmt($query, $binds);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function stream(string $command, array $binds): Generator
    {
        $stmt = $this->stmt($command, $binds);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function command(string $query, array $binds): int
    {
        $stmt = $this->stmt($query, $binds);
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function transaction(callable $callback): void
    {
        if ($this->pdo->inTransaction()) {
            $callback();
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $callback();
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function quote(mixed $value): string
    {
        return $this->pdo->quote($value, $this->type($value));
    }

    protected function stmt(string $query, array $binds = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($query);
        foreach ($binds as $index => $value) {
            $stmt->bindValue($index + 1, $value, $this->type($value));
        }
        return $stmt;
    }

    protected function type(mixed $value): int
    {
        $type = gettype($value);

        return match ($type) {
            'NULL' => PDO::PARAM_NULL,
            'boolean' => PDO::PARAM_BOOL,
            'integer' => PDO::PARAM_INT,
            'string' => PDO::PARAM_STR,
            default => throw new Exception('Unexpected type ' . $type),
        };
    }
}
