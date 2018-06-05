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
    $table->boolean('published')->notNull();
    $table->string('title')->notNull();
    $table->string('content')->notNull();

    $table->foreign('user_id')
        ->references('users', 'id')
        ->onUpdate('cascade')
        ->onUpdate('cascade');
});

$schema->create('profiles', function(CreateTable $table){
    $table->string('id', 32)->primary();
    $table->integer('user_id')->notNull()->index();
    $table->string('city')->notNull();

    $table->foreign('user_id')
        ->references('users', 'id')
        ->onUpdate('cascade')
        ->onUpdate('cascade');
});

$schema->create('tags', function(CreateTable $table){
    $table->string('id', 32)->primary();
});

$schema->create('ck_records', function(CreateTable $table){
    $table->integer('key1')->notNull();
    $table->integer('key2')->notNull();
    $table->string('data');
    $table->primary(['key1', 'key2']);
});

$schema->create('ck_related', function(CreateTable $table){
    $table->integer('id')->primary();
    $table->integer('ck_record_key1')->notNull();
    $table->integer('ck_record_key2')->notNull();
    $table->index(['ck_record_key1', 'ck_record_key2']);

    $table->foreign(['ck_record_key1', 'ck_record_key2'])
        ->references('ck_records', 'key1', 'key2')
        ->onUpdate('cascade')
        ->onDelete('cascade');
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

$schema->create('automated_entity_1', function(CreateTable $table){
    $table->integer('id')->autoincrement();
    $table->string('data')->notNull();
    $table->softDelete();
    $table->timestamps();
});


$schema->create('automated_entity_2', function(CreateTable $table){
    $table->integer('id')->autoincrement();
    $table->string('data')->notNull();
    $table->softDelete('d_at');
    $table->timestamps('c_at', 'u_at');
});

$data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);

foreach ($data as $table => $records) {
    foreach ($records as $record) {
        $db->insert($record)->into($table);
    }
}

unset($data);

\Opis\ORM\Test\entityManager(new EntityManager($connection));

