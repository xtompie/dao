<?php

declare(strict_types=1);

namespace Xtompie\Dao;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Exception;
use Generator;

class DoctrineAdapter implements Adapter
{
    public function __construct(
        protected Connection $connection,
    ) {}

    public function query(string $query, array $binds): array
    {
        return (array)$this->connection->fetchAllAssociative($query, $binds, $this->types($binds));
    }

    public function stream(string $query, array $binds): Generator
    {
        $result = $this->connection->executeQuery($query, $binds, $this->types($binds));
        yield from $result->iterateAssociative();
    }

    public function command(string $command, array $binds): int
    {
        return $this->connection->executeStatement($command, $binds, $this->types($binds));
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        if ($this->connection->isTransactionActive()) {
            return $callback();
        }

        if (!$this->connection->beginTransaction()) {
            throw new Exception();
        }

        try {
            $result = $callback();
            $this->connection->commit();
            return $result;
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    public function quote(mixed $value): string
    {
        return $this->connection->quote($value, $this->type(gettype($value)));
    }

    protected function types(array $binds): array
    {
        return array_map(fn (mixed $param) => $this->type(gettype($param)), $binds);
    }

    protected function type(string $type): int
    {
        return match ($type) {
            'NULL' => ParameterType::NULL,
            'boolean' => ParameterType::BOOLEAN,
            'integer' => ParameterType::INTEGER,
            'string' => ParameterType::STRING,
            default => throw new Exception('Unexpected type ' . $type),
        };
    }
}
