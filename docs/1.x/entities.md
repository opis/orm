---
layout: project
version: 1.x
title: Entities 
---
# Entities 

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

The base class provides a single method, named `orm`, which returns a
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

The constructor of the base entity class is marked as `final`, therefor
you can not provide a custom `__construct` method.

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

[0]: /1.x/data-mappers.html "Data mappers"
