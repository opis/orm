---
layout: project
version: 1.x
title: Entity mappers
---
# Entity mappers

1. [Introduction](#introduction)
2. [Auto-register](#auto-register)
3. [Contruct-time registration](#construct-time-registration)
4. [Manual registration](#manual-registration)

## Introduction
 
The purpose of entity mappers is to provide a simple and effective way 
of describing the relationship between an entity class and its corresponding table, 
or between a group of related entities.
Before we can use entity mappers, we must register them first and associated them
with entities.

## Auto-register

This is not only the most simple way of registering an entity mapper, but is also the
recommended way of doing it. The only thing you are required to do, is to implement the
`Opis\ORM\IEntityMapper` interface on your entity class.

```php
use Opis\ORM\{Entity, IEntityMapper, Core\EntityMapper};

class User extends Entity implements IEntityMapper
{
    public static function mapEntity(EntityMapper $mapper)
    {
        // Map entity here
    }
}
```

## Construct-time registration

Another way of registering an entity mapper is by registering it at the construct-time of the 
entity manager instance.

```php
use Opis\Database\Connection;
use Opis\ORM\{EntityManager, Core\EntityMapper};

// Define a database connection
$connection = new Connection("dsn:mysql;dbname=test", "root", "secret");

// Create an entity manager
$orm = new EntityManager($connection);

$entityMappers = [
    User::class => function(EntityMapper $mapper){
        // Map entity here
    },
    Article::class => function(EntityMapper $mapper){
        // Map entity here
    },
];

$orm = new EntityMapper($connection, $entityMappers);
```

## Explicit registration

The explicit registration of an entity mapper is done by using the `registerEntityMapper` method on the
entity manager instance.

```php
use Opis\Database\Connection;
use Opis\ORM\{EntityManager, Core\EntityMapper};

// Define a database connection
$connection = new Connection("dsn:mysql;dbname=test", "root", "secret");

// Create an entity manager
$orm = new EntityManager($connection);

$orm->registerEntityMapper(User::class, function(EntityMapper $mapper){
    // Map entity here
});

$orm->registerEntityMapper(Article::class, function(EntityMapper $mapper){
    // Map entity here
});
```