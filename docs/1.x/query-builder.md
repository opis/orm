---
layout: project
version: 1.x
title: Query builder
---
# Query builder

1. [Fetching entities](#fetching-entities)
2. [Building queries](#building-queries)
3. [Updating entities](#updating-entities)
4. [Deleting entities](#deleting-entities)

The query builder is based on the infrastructure provided by Opis Database library,
and provides methods for fetching, updating or deleting records. 

## Fetching entities

The query builder provides several methods for fetching records as entities

### find

This method is used when you want to fetch an entity by its ID.

```php
use Opis\ORM\EntityManager;
use Opis\Database\Connection;
use My\Blog\User;

$connection = new Connection("mysql:dbname=test", "root", "secret");
$orm = new EntityManager($connection);

$user = $orm->query(User::class)->find(1);

// Or, alternatively

$user = $orm(User::class)->find(1);
```

### findAll

This methods takes as arguments as series of entity IDs and try to fetch them.

```php
$users = $orm(User::class)->findAll(1, 2, 3, 4); // Use multiple IDs
```

### all

This methods returns an array of entity instances, or an empty array if no records were found

```php
// Returns all entities
$users = $orm(User::class)->all();
```

### get

Use this method for fetching a single instance of an entity from a query. This
will return `null` if no entity was fetched.

```php
// Returns one entity instance
$users = $orm(User::class)->get();
```

## Building queries

### Filter

You can filter the entities returned by a query by using the `where` method.

```php
//** User[] $users */
$users = $orm(User::class)
                ->where('age')->atLeast(18)
                ->all();
```

### Order

Entities can be ordered by using the `orderBy` method.

```php
//** User[] $users */
$users = $orm(User::class)
                ->where('age')->atLeast(18)
                ->orderBy('age', 'asc')
                ->all();
```

### Offsets and limits

You can limit the number of entities your query returns by using the `limit` method.

```php
//** User[] $users */
$users = $orm(User::class)
                ->where('age')->atLeast(18)
                ->orderBy('age', 'asc')
                ->limit(10)
                ->all();
```

Use the `offset` method in conjunction with `limit` to skip and ignore the first
`n` records.

```php
$users = $orm(User::class)
                ->where('age')->atLeast(18)
                ->orderBy('age', 'asc')
                ->offset(20) // Ignore first 20 records
                ->limit(10)
                ->all();
```

## Updating entities

Updating multiple entities from a single query is possible by using the `update` method.

```php
$orm(User::class)
    ->where('age')->is(18)
    ->update([
        'age' => 19
    ]);
```

You can use the `increment` and `decrement` methods to increment or decrement the value of column.

```php
$orm(User::class)
        ->where('age')->is(18)
        ->increment('age'); // Increment by 1
                
$orm(User::class)
       ->where('age')->is(21)
       ->decrement('age', 2); // Decrement by 2             
```

## Deleting entities

Deleting multiple entities at once is done by using the `delete` method.

```php
// Delete all users
$orm(User::class)->delete();

// Delete users under 13
$orm(User::class)
    ->where('age')->lessThan(13)
    ->delete(); 
```
