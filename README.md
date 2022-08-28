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
It can be change by creating adapter like `Xtompie\Dao\Adapter` and pass it to Dao constructor.

For more info check source.

