<?php

declare(strict_types=1);

namespace Xtompie\Dao;

use Generator;
use ReflectionClass;

/**
 * @template Item
 * @template Collection
 */
class Repository
{
    /**
     * @param Dao $dao
     * @param string|null $table
     * @param class-string<Collection>|null $collectionClass
     * @param class-string<Item>|null $itemClass
     * @param callable(array<string,mixed>):Item|null $itemFactory
     * @param array<string,mixed> $static
     * @param callable():array<string,mixed>|null $callableStatic
     */
    public function __construct(
        protected Dao $dao,
        protected ?string $table = null,
        protected ?string $collectionClass = null,
        protected ?string $itemClass = null,
        protected mixed $itemFactory = null,
        protected array $static = [],
        protected mixed $callableStatic = null,
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

    /**
     * @param array<string,mixed> $static
     */
    public function withStatic(array $static): static
    {
        $new = clone $this;
        $new->static = $static;
        return $new;
    }

    /**
     * @param callable():array<string,mixed> $static
     */
    public function withCallableStatic(callable $static): static
    {
        $new = clone $this;
        $new->callableStatic = $static;
        return $new;
    }

    /**
     * @param array<mixed>|null $where
     * @param string|null $order
     * @param int|null $offset
     * @return Item|null
     */
    public function find(?array $where = null, ?string $order = null, ?int $offset = null): mixed
    {
        return $this->item(
            tuple: $this->dao->record(
                table: $this->table(),
                where: $this->where($where),
                order: $order,
                offset: $offset,
            ),
        );
    }

    /**
     * @param array<mixed>|null $where
     * @param string|null $order
     * @param int|null $limit
     * @param int|null $offset
     * @return Collection
     */
    public function findAll(?array $where = null, ?string $order = null, ?int $limit = null, ?int $offset = null): mixed
    {
        return $this->items(
            tuples: $this->dao->records(
                table: $this->table(),
                where: $this->where($where),
                order: $order,
                limit: $limit,
                offset: $offset
            ),
        );
    }

    /**
     * @param array<mixed>|null $where
     * @param string|null $order
     * @param int|null $limit
     * @param int|null $offset
     * @return Generator<Item>
     */
    public function stream(?array $where = null, ?string $order = null, ?int $limit = null, ?int $offset = null): Generator
    {
        foreach (
            $this->dao->streamRecords(
                table: $this->table(),
                where: $this->where($where),
                order: $order,
                limit: $limit,
                offset: $offset
            )
            as $tuple
        ) {
            yield $this->item($tuple);
        }
    }

    /**
     * @param array<mixed>|null $where
     * @param string|null $group
     * @param string|null $count
     */
    public function count(?array $where = null, ?string $group = null, ?string $count = null): int
    {
        return $this->dao->count(
            array_filter([
                'from' => $this->table(),
                'where' => $this->where($where),
                'group' => $group
            ]),
            $count
        );
    }

    /**
     * @param array<mixed>|null $where
     */
    public function exists(?array $where = null): bool
    {
        return $this->dao->exists(table: $this->table(), where: $this->where($where));
    }

    /**
     * @param array<string,mixed> $values
     */
    public function insert(array $values): void
    {
        $this->dao->insert(table: $this->table(), values: $this->value($values));
    }

    /**
     * @param array<string,mixed> $values
     */
    public function update(array $set, array $where, bool $patch = false): int
    {
        if ($patch) {
            $set = array_filter($set, fn ($v) => $v !== null);
            if (!$set) {
                return 0;
            }
        }
        return $this->dao->update(table: $this->table(), set: $this->value($set), where: $this->where($where));
    }

    /**
     * @param array<string,mixed> $values
     */
    public function upsert(array $set, array $where): void
    {
        $this->dao->upsert(table: $this->table(), set: $this->value($set), where: $this->where($where));
    }

    /**
     * @param array<string,mixed> $values
     */
    public function delete(array $where): void
    {
        $this->dao->delete(table: $this->table(), where: $this->where($where));
    }

    /**
     * @param array<string,mixed> $values
     */
    public function patch(array $set, array $where): int
    {
        return $this->update(set: $set, where: $where, patch: true);
    }

    /**
     * @param mixed $id
     * @param array<string,mixed> $values
     */
    public function patchId(mixed $id, array $set): int
    {
        return $this->patch(set: $set, where: ['id' => $id]);
    }

    protected function table(): string
    {
        if (!$this->table) {
            throw new \RuntimeException('Table not set');
        }
        return $this->table;
    }

    /**
     * @param array<string,mixed>|null $tuple
     * @return Item|null
     */
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

    /**
     * @param array<array<string,mixed>> $tuples
     * @return Collection
     */
    protected function items(array $tuples): mixed
    {
        $tuples = array_map(fn (array $tuple) => $this->item($tuple), $tuples);

        if ($this->collectionClass) {
            return (new ReflectionClass($this->collectionClass))->newInstance($tuples);
        }

        return $tuples;
    }

    /**
     * @param array<string,mixed>|null $where
     */
    protected function where(?array $where): ?array
    {
        $combine = [];
        if ($where) {
            $combine = $where;
        }
        if ($this->static) {
            $combine = array_merge($combine, $this->static);
        }
        if ($this->callableStatic) {
            $combine = array_merge($combine, ($this->callableStatic)());
        }
        return $combine ?: null;
    }

    /**
     * @param array<string,mixed> $value
     */
    protected function value($value): array
    {
        if ($this->static) {
            $value = array_merge($value, $this->static);
        }
        if ($this->callableStatic) {
            $value = array_merge($value, ($this->callableStatic)());
        }
        return $value;
    }
}
