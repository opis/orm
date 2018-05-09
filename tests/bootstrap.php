<?php
namespace Opis\ORM\Test;

require_once __DIR__ . '/../vendor/autoload.php';

use Opis\ORM\Core\EntityQuery;
use Opis\ORM\EntityManager;
use Opis\Database\{
    Database, Connection,
    Schema\CreateTable
};

/**
 * @param EntityManager|null $instance
 * @return EntityManager
 */
function entityManager(EntityManager $instance = null): EntityManager
{
    static $manager;
    if ($instance !== null) {
        $manager = $instance;
    }
    return $manager;
}

function query(string $class): EntityQuery
{
    return \Opis\ORM\Test\entityManager()->query($class);
}

function unique_id(): string
{
    return bin2hex(random_bytes(16));
}

$file = __DIR__ . '/db.sql';

if (file_exists($file)) {
    unlink($file);
}

$connection = new Connection('sqlite:' . $file);
$connection->initCommand('PRAGMA foreign_keys = ON');
$connection->logQueries();
$db = new Database($connection);
$schema = $db->schema();

$schema->create('users', function(CreateTable $table) {
    $table->integer('id')->primary();
    $table->string('name')->notNull();
    $table->integer('age')->size('small')->notNull();
    $table->string('gender', 1)->notNull();
});

$schema->create('articles', function(CreateTable $table){
    $table->string('id', 32)->primary();
    $table->integer('user_id')->notNull()->index();
    $table->string('title')->notNull();
    $table->string('content')->notNull();

    $table->foreign('user_id')
        ->references('users', 'id')
        ->onUpdate('cascade')
        ->onUpdate('cascade');
});

$schema->create('tags', function(CreateTable $table){
    $table->string('id', 32)->primary();
});

$schema->create('articles_tags', function(CreateTable $table){
    $table->string('article_id', 32);
    $table->string('tag_id', 32);
    $table->primary(['article_id', 'tag_id']);
    $table->foreign('article_id')
        ->references('articles', 'id')
        ->onDelete('cascade')
        ->onUpdate('cascade');
    $table->foreign('tag_id')
        ->references('tags', 'id')
        ->onDelete('cascade')
        ->onUpdate('cascade');
});

$data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);

foreach ($data as $table => $records) {
    foreach ($records as $record) {
        $db->insert($record)->into($table);
    }
}

unset($data);

\Opis\ORM\Test\entityManager(new EntityManager($connection));

