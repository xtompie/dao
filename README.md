# DAO

DAO - Data Access Object - wrapper over SQL

```php
/** @var Xtompie\Dao\Dao $dao */
$dao->insert('user', ['email' => 'john.doe@exmaple.com', 'created_at' => time()]);
$records = $dao->query(['select' => '*', 'from' => 'user', 'limit' => 10]);
```

- [DAO](#dao)
  - [Requiments](#requiments)
  - [Installation](#installation)
  - [Docs](#docs)
    - [Createing Dao instance](#createing-dao-instance)
    - [Read](#read)
    - [Read from one table](#read-from-one-table)
    - [Write](#write)
    - [Transaction](#transaction)
    - [Extends AQL](#extends-aql)

## Requiments

PHP >= 8.0

## Installation

Using [composer](https://getcomposer.org/)

```
composer require xtompie/dao
```

## Docs

### Createing Dao instance

```php
use PDO;
use Xtompie\Aql\Aql;
use Xtompie\Aql\PostgreSQLPlatform;
use Xtompie\Dao\Dao;

$dao = new Dao(
    adapter: new PdoAdapter(pdo: new PDO('pgsql:host=localhost;dbname=test', 'postgres')),
    aql: new Aql(platform: new PostgreSQLPlatform()),
);
```

Available bulid-in adapters `Xtompie\Dao\Adapter`:

- `Xtompie\Dao\DoctrineAdapter`
- `Xtompie\Dao\PdoAdapter`

Uses [AQL](https://github.com/xtompie/aql) format to build sql queries.


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

    public function stream(array $query): Generator {}
    // yield records
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

    public function streamRecords(string $table, ?array $where = null, ?string $order = null, ?int $offset = null, ?int $limit = null): Generator
    // yield records

}
```

### Write

```php
class Dao
{
    public function command(array $command): int {}

    public function insert(string $table, array $values): int {}

    public function insertBulk(string $table, array $bluk): int {}

    public function update(string $table, array $set, array $where): int {}

    public function upsert(string $table, array $set, array $where): int {}

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
            unset($aql['paging']);
            if (!$paging instanceof Paging) {
                throw new \Exception();
            }
            $aql['offset'] => $paging->offset();
            $aql['limit'] => $paging->limit();
        }
        return parent::aql($aql);
    }
}

```
