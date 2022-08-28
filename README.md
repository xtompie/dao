# DAO

DAO - Data Access Object - wrapper over SQL

```php
/** @var Dao $dao */
$dao->insert('user', ['email' => 'john.doe@exmaple.com', 'created_at' => time()]);
$records = $dao->query(['select' => '*', 'from' => 'user', 'limit' => 10]);
```

## Requiments

PHP >= 8.0


## Installation

Using [composer](https://getcomposer.org/)

```
composer require xtompie/dao
```

## Docs

Uses [AQL](https://github.com/xtompie/aql) format to build sql queries.

Queries are executed throught `Doctrine\DBAL\Connection`.
It can be change by creating adapter like `Xtompie\Dao\Adapter` and pass it to Dao
constructor.

### Read

```php
class Dao
{
    public function query(array $query): array {}
    // returns all selected rows

    public function first(array $query): ?array {}
    // return first selected row or null

    public function val(array $query): mixed {}
    // return first field from first selected row or null

    public function any(array $query): bool {}
    // return true if there is any data for given query else false

    public function count(array $query): int {}
    // returns number of selected rows, by default uses `COUNT(*)`
}
```

### Read from one table

```php
class Dao
{
    public function records(string $table, ?array $where, ?string $order = null, ?int $offset = null, ?int $limit = null): array {}
    // similiar to `query`

    public function record(string $table, ?array $where = null, ?string $order = null, ?int $offset = null): ?array {}
    // similar to `first`

    public function amount(string $table, ?array $where = null, ?string $group = null): int {}
    // similiar to `count`

    public function exists(string $table, array $where): bool {}
    // similiar to `any`
}
```

### Write

```php
class Dao
{
    public function command(array $command): int

    public function insert(string $table, array $set): int

    public function update(string $table, array $set, array $where): int {}

    public function delete(string $table, array $where): int {}
}
```

Each method returns the number of affected rows

### Transaction

```php
class Dao
{
    public function transaction(callable $callback) {}
    // run callback in transaction
}
```

### Extends AQL


```php

namespace App\Shared\Dao;

use Xtompie\Dao\Dao as BaseDao;

interface Paging
{
    public function limit(): int;
    public function offset(): int;
}

class Dao extends BaseDao
{
    public function aql(array $aql): array
    {
        if (isset($aql['paging'])) {
            $paging = $aql['paging'];
            if (!$paging instanceof Paging) {
                throw new \Exception();
            }
            $aql['offset'] => $paging->offset();
            $aql['limit'] => $paging->limit();
            unset($aql['paging']);
        }
        return parent::aql($aql);
    }
}

```