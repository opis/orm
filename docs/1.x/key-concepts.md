---
layout: project
version: 1.x
title: Key concepts
---
# Key concepts

1. [Database connection](#database-connection)
2. [Entities](#entities)
3. [The entity manager](#the-entity-manager)
4. [Entity mappers](#entity-mappers)

## Database connection

The very first thing you must do in order to be able to use the ORM is to
define a connection to your database. The connection is defined with the help
of the `Opis\Database\Connection` class, on whom you can find out more [here][0]. 

```php
use Opis\Database\Connection;

$connection = new Connection("dsn:mysql;dbname=test", "root", "secret");
```

## Entities

Entities are an object-oriented representation of your database tables.
Each defined entity must be a descendant of the `Opis\ORM\Entity` class.

```php
use Opis\ORM\Entity;

class User extends Entity
{
    // An entity class for the 'users' table
}
```

## The entity manager

The main function of the entity manager is to provide methods for creating, fetching,
updating, and deleting an entity. It is represented by an instance of the 
`Opis\ORM\EntityManager` class. 

```php
use Opis\Database\Connection;
use Opis\ORM\EntityManager;

// Define a database connection
$connection = new Connection("dsn:mysql;dbname=test", "root", "secret");

// Create an entity manager
$orm = new EntityManager($connection);
```

## Entity mappers

[Entity mappers][1] are instances of the `Opis\ORM\Core\EntityMapper` class that are used
to map an entity to its corresponding table.

## Data mappers

[Data mappers][2] are instance of the `Opis\ORM\Core\DataMapper` class that are used
to 

[0]: /database/4.x/connections "Opis Database"
[1]: entity-mappers.html "Entity mappers"
[2]: data-mappers.html "Data mappers"
