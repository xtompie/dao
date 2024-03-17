<?php

declare(strict_types=1);

namespace Xtompie\Dao;

use Exception;
use Generator;
use Xtompie\Aql\Aql;

class Dao
{
    public function __construct(
        protected Adapter $adapter,
        protected Aql $aql,
    ) {}

    public function query(array $query): array
    {
        return (array)$this->adapter->query(...$this->sql($query));
    }

    public function command(array $command): int
    {
        return $this->adapter->command(...$this->sql($command));
    }

    public function stream(array $query): Generator
    {
        yield from $this->adapter->stream(...$this->sql($query));
    }

    public function streamRecords(string $table, ?array $where = null, ?string $order = null, ?int $offset = null, ?int $limit = null): Generator
    {
        foreach (
            $this->stream(array_filter([
                'select' => '*',
                'from' => $table,
                'where' => $where,
                'order' => $order,
                'offset' => $offset,
                'limit' => $limit,
            ])) as $tuple
        ) {
            yield $tuple;
        }
    }

    public function first(array $query): ?array
    {
        return array_values($this->query($query))[0] ?? null;
    }

    public function val(array $query): mixed
    {
        return array_values($this->first($query))[0] ?? null;
    }

    public function any(array $query): bool
    {
        return (bool)$this->first($query);
    }

    public function count(array $query, ?string $count = null): int
    {
        $count = $count ?: '*';
        return (int) $this->val(array_merge($query, ['select' => "COUNT($count)"]));
    }

    public function records(string $table, ?array $where, ?string $order = null, ?int $offset = null, ?int $limit = null): array
    {
        return $this->query(array_filter([
            'select' => '*',
            'from' => $table,
            'where' => $where,
            'order' => $order,
            'offset' => $offset,
            'limit' => $limit,
        ]));
    }

    public function record(string $table, ?array $where = null, ?string $order = null, ?int $offset = null): ?array
    {
        return $this->records($table, $where, $order, $offset, 1)[0] ?? null;
    }

    public function amount(string $table, ?array $where = null, ?string $group = null): int
    {
        return $this->count(array_filter(['from' => $table, 'where' => $where, 'group' => $group]));
    }

    public function exists(string $table, array $where): bool
    {
        return $this->any(['select' => '*', 'from' => $table, 'where' => $where]);
    }

    public function insert(string $table, array $values): int
    {
        return $this->command([
            'insert' => $table,
            'values' => $values,
        ]);
    }

    public function insertBulk(string $table, array $bluk): int
    {
        return $this->command([
            'insert' => $table,
            'values_bulk' => $bluk,
        ]);
    }

    public function update(string $table, array $set, array $where): int
    {
        return $this->command([
            'update' => $table,
            'set' => $set,
            'where' => $where,
        ]);
    }

    public function upsert(string $table, array $set, array $where): int
    {
        return $this->exists(table: $table, where: $where)
            ? $this->update(table: $table, set: $set, where: $where)
            : $this->insert(table: $table, values: [...$where, ...$set])
        ;
    }

    public function delete(string $table, array $where): int
    {
        if (!$where) {
            throw new Exception('Deleteing without where condition is not allowed');
        }

        return $this->command([
            'delete' => $table,
            'where' => $where,
        ]);
    }

    public function transaction(callable $callback)
    {
        return $this->adapter->transaction($callback);
    }

    public function quote(mixed $value): mixed
    {
        return $this->adapter->quote($value);
    }

    protected function aql(array $aql): array
    {
        return $aql;
    }

    protected function sql(array $aql): array
    {
        return ($this->aql)($this->aql($aql))->toArray();
    }
}
