---
layout: project
version: 1.x
title: Entity manager
---
# Entity manager

1. [Instantiation](#instantiation)
2. [Creating entities](#creating-entities)
3. [Persisting entities](#persisting-entities)
4. [Fetching entities](#fetching-entities)
5. [Deleting entities](#deleting-entities)


## Instantiation

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

## Creating entities

Entities are created with the help of the `create` method. This method
takes as an argument the class name of the entity.

```php
use My\Blog\User;

/**
 * Create a new entity
 * @var $user \My\Blog\User
 */
$user = $orm->create(User::class);
```

## Persisting entities

The newly created entity is not persisted into the database until
the `save` method is called. This method takes as an argument an instance
of an entity and returns `true` if the entity was successfully persisted,
or `false` otherwise.

```php
if ($orm->save($user)) {
    echo "Entity was saved";
} else {
    die("Something went wrong");
}
```

The same method can be used to persist an existing entity after it was modified.

```php
use My\Blog\User;

$user = $orm->create(User::class);
$user->setName('Foo');

if (!$orm->save($user)) {
    die("Something went wrong");
}

// Modify entity
$user->setName('Bar');
// Update entity
$orm->save($user);
```


## Fetching entities

Fetching existing records is done with the help of the `query` method.
The method returns an instance of a [query builder][0] that provides 
various methods that can be used to filter records.

```php
// Get a User entiy by ID
$user = $orm->query(User::class)->find(1);
```

You can achieve the same thing as above by directly invoking the
entity manager.

```php
// Get a User entiy by ID
$user = $orm(User::class)->find(1);
```


## Deleting entities

Deleting an existing entity is done with the help of the `delete` method.
The method takes as an argument an entity instance and returns `true`
if the entity is successfully deleted, or `false` otherwise.

```php
if (!$orm->delete($user)) {
    die('Could not delete user');
}
```

[0]: query-builder.html "Query builder"