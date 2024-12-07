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

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $callback();
        }

        $this->pdo->beginTransaction();

        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;
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
