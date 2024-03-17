<?php

declare(strict_types=1);

namespace Xtompie\Dao;

use Generator;

interface Adapter
{
    public function query(string $query, array $binds): array;

    public function stream(string $command, array $binds): Generator;

    public function command(string $command, array $binds): int;

    public function transaction(callable $callback): void;

    public function quote(mixed $value): string;
}
