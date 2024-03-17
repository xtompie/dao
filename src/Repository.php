<?php

declare(strict_types=1);

namespace Xtompie\Dao;

use Generator;
use ReflectionClass;

class Repository
{
    public function __construct(
        protected Dao $dao,
        protected ?string $table = null,
        protected ?string $collectionClass = null,
        protected ?string $itemClass = null,
        protected mixed $itemFactory = null,
    ) {
    }

    public function withTable(string $table): static
    {
        $new = clone $this;
        $new->table = $table;
        return $new;
    }

    public function withCollectionClass(string $collectionClass): static
    {
        $new = clone $this;
        $new->collectionClass = $collectionClass;
        return $new;
    }

    public function withItemClass(string $itemClass): static
    {
        $new = clone $this;
        $new->itemClass = $itemClass;
        return $new;
    }

    public function withItemFactory(callable $itemFactory): static
    {
        $new = clone $this;
        $new->itemFactory = $itemFactory;
        return $new;
    }

    public function find(?array $where = null, ?string $order = null, ?int $offset = null): mixed
    {
        return $this->item(
            tuple: $this->dao->record(
                table: $this->table(),
                where: $where,
                order: $order,
                offset: $offset,
            ),
        );
    }

    public function findAll(?array $where = null, ?string $order = null, ?int $limit = null, ?int $offset = null): mixed
    {
        return $this->items(
            tuples: $this->dao->records(
                table: $this->table(),
                where: $where,
                order: $order,
                limit: $limit,
                offset: $offset
            ),
        );
    }

    public function stream(?array $where = null, ?string $order = null, ?int $limit = null, ?int $offset = null): Generator
    {
        foreach (
            $this->dao->streamRecords(table: $this->table(), where: $where, order: $order, limit: $limit, offset: $offset)
            as $tuple
        ) {
            yield $this->item($tuple);
        }
    }

    public function count(?array $where = null, ?string $group = null, ?string $count = null): int
    {
        return $this->dao->count(array_filter(['from' => $this->table(), 'where' => $where, 'group' => $group]), $count);
    }

    public function exists(?array $where = null): bool
    {
        return $this->dao->exists(table: $this->table(), where: $where);
    }

    public function insert(array $values): void
    {
        $this->dao->insert(table: $this->table(), values: $values);
    }

    public function update(array $set, array $where, bool $patch = false): void
    {
        if ($patch) {
            $set = array_filter($set, fn ($v) => $v !== null);
            if (!$set) {
                return;
            }
        }
        $this->dao->update(table: $this->table(), set: $set, where: $where);
    }

    public function upsert(array $set, array $where): void
    {
        $this->dao->upsert(table: $this->table(), set: $set, where: $where);
    }

    public function delete(array $where): void
    {
        $this->dao->delete(table: $this->table(), where: $where);
    }

    public function patch(array $set, array $where): void
    {
        $this->update(set: $set, where: $where, patch: true);
    }

    public function patchId(mixed $id, array $set): void
    {
        $this->patch(set: $set, where: ['id' => $id]);
    }

    protected function table(): string
    {
        if (!$this->table) {
            throw new \RuntimeException('Table not set');
        }
        return $this->table;
    }

    protected function item(?array $tuple): mixed
    {
        if (!$tuple) {
            return null;
        } elseif ($this->itemFactory) {
            return ($this->itemFactory)($tuple);
        } elseif ($this->itemClass && class_exists($this->itemClass)) {
            return (new ReflectionClass($this->itemClass))->newInstance($tuple);
        } else {
            return $tuple;
        }
    }

    protected function items(array $tuples): mixed
    {
        $tuples = array_map(fn (array $tuple) => $this->item($tuple), $tuples);

        if ($this->collectionClass) {
            return (new ReflectionClass($this->collectionClass))->newInstance($tuples);
        }

        return $tuples;
    }
}
