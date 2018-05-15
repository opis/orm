---
layout: project
version: 1.x
title: Key concepts
---
# Key concepts

1. [Entities](#entities)
2. [The entity manager](#the-entity-manager)
3. [Entity mappers](#entity-mappers)

## Entities

An entity is an object-oriented representation of an SQL table.
Entity classes are derived from the `Opis\ORM\Entity` base class, 
and each instance of such a class is a direct mapping to a row of its
corresponding table.

```php
use Opis\ORM\Entity;

class User extends Entity
{
    // User entity
}
```

The entity base class, provides a single method, named `orm`, which returns a
[data mapper][0] object, that can be used to manipulate the row's records.


```php
use Opis\ORM\Entity;

class User extends Entity
{
    public function name(): string
    {
        return $this->orm()->getColumn('name');
    }
}
```
Since an entity is not meant to be directly instantiable,
the constructor of the base entity class is marked as being `final`, 
in order to prevent to be accidentally overwritten.

```php
use Opis\ORM\Entity;

class User extends Entity
{
    public function __construct()
    {
        // This will throw an exception
    }
}
```

## The entity manager

The entity manager is represented by the `Opis\ORM\EntityManager` class,
and its main function is to provide methods for creating, fetching,
updating, and deleting entities. 

The constructor of the entity manager takes as an argument an instance of
the `Opis\Database\Connection` class, that will be further used to 
establish a connection to the database.

```php
use Opis\ORM\EntityManager;
use Opis\Database\Connection;

$connection = new Connection("mysql:dbname=test", "root", "secret");
$orm = new EntityManager($connection);
```
### Create a new entity

The entity manager can be used to instantiate a new entity. This is done
with the help of the `create` method. The method takes as an argument
the class name of the entity.

```php
use My\Blog\User;

/**
 * Create a new entity
 * @var $user \My\Blog\User
 */
$user = $orm->create(User::class);
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
