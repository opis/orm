---
layout: project
version: 1.x
title: Mapping entities
---
# Entity mapping

1. [Table name](#table-name)
2. [Primary key](#primary-key)
3. [Primary key generator](#primary-key-generator)
4. [Sequence object's name](#sequence-objects-name)

## Table name

The corresponding table of an entity is derived from the entity's class name,
in lowercase, + the `s` suffix. Therefor, if a class `My\Blog\User` represents an entity, then
the corresponding table would be `users`. 
Of course, this is not always a desired behavior. Changing the table's name is
simply a matter of calling the `table` method on the entity mapper instance.

```php
class User extends Entity implements IEntityMapper
{
    public static function mapEntity(EntityMapper $mapper)
    {
        $mapper->table('registred_users');
    }
}
```

## Primary key

By default, **Opis ORM** assumes that your table's primary key column is named `id`. 
If your primary key column has a different name, you must specify this by using the
`primaryKey` method.

```php
class User extends Entity implements IEntityMapper
{
    public static function mapEntity(EntityMapper $mapper)
    {
        $mapper->primaryKey('user_id');
    }
}
```

## Primary key generator

You can use a generator to automatically assign a value for your primary key.
Registering a generator is done by passing a callback to the `primaryKeyGenerator` method.

```php
class User extends Entity implements IEntityMapper
{
    public static function mapEntity(EntityMapper $mapper)
    {
        $mapper->primaryKeyGenerator(function(){
            // Generate PK
            return generate_some_pk();
        });
    }
}
```

## Sequence object's name

**Opis ORM** allows you to create entities without explicitly adding a value for
the [primary key](#primary-key). This is useful when you are relying on the 
auto-increment mechanism of a database system to assign an ID for your newly created
record. To retrieve the ID of the newly created record, **Opis ORM** uses the PDO's 
`lastInsertId` method, which require - for some database systems, like PostgreSQL - to
provide the name of the sequence object from which the ID will be returned. 
By default, this name is constructed using the following pattern: *table name* + `_` +
*primary key* + `_seq`. For an entity `My\Blog\User` with a primary key column named `id`,
the name of the sequence object would be `users_id_seq`. If your sequence object is named
differently, you must specify this by using the `sequence` method.

```php
class User extends Entity implements IEntityMapper
{
    public static function mapEntity(EntityMapper $mapper)
    {
        $mapper->sequence('table_users_seq');
    }
}
```
