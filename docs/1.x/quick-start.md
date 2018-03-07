---
layout: project
version: 1.x
title: Quick start
---
# Quick start

1. [The entity manager](#the-entity-manager)
2. [Defining entities](#defining-entities)
3. [Working with entities](#working-with-entities)

This is a quick overview of the library and of its features. 
Don't worry if you don't understand every single line of code; 
everything will be explained later in detail.
{:.alert.alert-light.text-dark}

## The entity manager

The main function of the entity manager is to provide methods for creating, fetching,
updating, and deleting an entity. The very first thing you must do, 
in order to be able to use the entity manager, is to define a connection to your 
database. The connection is defined with the help of the `Opis\Database\Connection` 
class, on whom you can find out more [here][0]. 

Once the connection has been defined, it's time to instantiate the entity manager.

```php
use Opis\Database\Connection;
use Opis\ORM\EntityManager;

// Define a database connection
$connection = new Connection("dsn:mysql;dbname=test", "root", "secret");

// Create an entity manager
$orm = new EntityManager($connection);
```

## Defining entities

Entities are an object-oriented representation of your database tables.
They are represented with the help of classes and each instance of such 
a class it's a direct mapping to a table's record. Entity classes inherit
from `Opis\ORM\Entity` base class, which provides a single method, named
`orm`, that returns a data mapper object. 
The data mapper object has various methods that allows you to interact 
with the entity's records.

Let's define two entities: `My\Blog\User` and `My\Blog\Article`, and see how
we can use them.

#### User entity

This class will contain, at first, only two methods: a method for setting the name of
the user, and another one for getting their name. As you can see, this is done with the help of 
the `getColumn` and `setColumn` methods.

```php
namespace My\Blog;

use Opis\ORM\Entity;

class User extends Entity
{
    /**
     * Get user's name
     * @return string
     */
    public function getName(): string
    {
        return $this->orm()->getColumn('name');
    }

    /**
     * Set user's name
     * @param string $name
     * @return User
     */
    public function setName(string $name): self
    {
        $this->orm()->setColumn('name', $name);
        return $this;
    }
}
```

#### Article entity

The class for the *Article* entity is pretty much similar with the previous one.

```php
namespace My\Blog;

use Opis\ORM\Entity;

class Article extends Entity
{
    /**
     * Get article's title
     * @return string
     */
    public function getTitle(): string
    {
        return $this->orm()->getColumn('title');
    }

    /**
     * Get article's content
     * @return string
     */
    public function getContent(): string
    {
        return $this->orm()->getColumn('content');
    }

    /**
     * Set articles's title
     * @param string $title
     * @return Article
     */
    public function setTitle(string $title): self
    {
        $this->orm()->setColumn('title', $title);
        return $this;
    }

    /**
     * Set articles's content
     * @param string $content
     * @return Article
     */
    public function setContent(string $content): self
    {
        $this->orm()->setColumn('content', $content);
        return $this;
    }
}
```

#### Relationships between entities


In our example, the *Article* entity doesn't make sense without a *User* entity.
That's because an article is something that a user creates. 
So, in other words: every *Article* belongs to a *User*, and a *User* can have multiple *Article*s.

In order to express this relationship, between the *Article* and the *User* entity, 
we must first associate our entity with an entity mapper.
The most simple way of doing that, it's by implementing the
`Opis\ORM\IEntityMapper` interface on our entity class.

Then, we can simply tell the entity mapper that the *Article*
belongs to a *User*, by defining a `belongs to` relation, and name it `author`.

```php
namespace My\Blog;

use Opis\ORM\{
    Entity, 
    IEntityMapper,
    Core\EntityMapper
};

class Article extends Entity implements IEntityMapper
{
    // ... other methods here

    /**
     * @inheritdoc
     */
    public static function mapEntity(EntityMapper $mapper)
    {
        // Establish a belongs-to relationship with the User entity
        $mapper->relation('author')->belongsTo(User::class);
    }
}
```

Now that we have defined our relationship, let's use it inside our class.

```php
namespace My\Blog;

use Opis\ORM\{
    Entity, 
    IEntityMapper,
    Core\EntityMapper
};

class Article extends Entity implements IEntityMapper
{
    // ... other methods here
    
    /**
     * Get article's author
     * @return User
     */
    public function getAuthor(): User
    {
        return $this->orm()->getRelated('author');
    }

    /**
     * Set article's author
     * @param User $user
     * @return Article
     */
    public function setAuthor(User $user): self
    {
        $this->orm()->setRelated('author', $user);
        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public static function mapEntity(EntityMapper $mapper)
    {
        // Establish a belongs-to relationship with the User entity
        $mapper->relation('author')->belongsTo(User::class);
    }
}
```

The same thing can be done regarding the *User* entity. 
Here, we need to tell the entity mapper that a user could have
multiple articles, and then we'll define a method that will use that relationship.

```php
namespace My\Blog;

use Opis\ORM\{
    Entity, 
    IEntityMapper,
    Core\EntityMapper
};

class User extends Entity implements IEntityMapper
{
    // ... other methods here
    
    /**
     * Get user's articles
     * @return Article[]
     */
    public function getArticles(): array
    {
        return $this->orm()->getRelated('articles');
    }
    
    /**
     * @inheritdoc
     */
    public static function mapEntity(EntityMapper $mapper)
    {
        // Establish a "has many" relationship with the Article entity
        $mapper->relation('articles')->hasMany(Article::class);
    }
}
```

## Working with entities

Creating a new entity is accomplished by calling the `create` method on the entity manager
instance. The newly created entity will not be persisted into the database, until the
`save` method is called.

```php
use Opis\Database\Connection;
use Opis\ORM\EntityManager;
use My\Blog\{User, Article};

// Define a database connection
$connection = new Connection("dsn:mysql;dbname=test", "root", "secret");

// Create an entity manager
$orm = new EntityManager($connection);

// Create a new entity
$user = $orm->create(User::class);
// Set user's name
$user->setName('Admin');
// Persist our entity
$orm->save($user);

// Create article
$article = $orm->create(Article::class);

// Setup article
$article->setTitle('My first article')
        ->setContent("This is my article's content")
        ->setAuthor($user);

$orm->save($article);
```

[0]: /database/4.x/connections "Opis Database"
