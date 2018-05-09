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

$file = __DIR__ . '/db.sql';

if (file_exists($file)) {
    unlink($file);
}

$connection = new Connection('sqlite:' . $file);
$connection->initCommand('PRAGMA foreign_keys = ON');

$db = new Database($connection);
$schema = $db->schema();

$schema->create('users', function(CreateTable $table) {
    $table->integer('id')->primary()->autoincrement();
    $table->string('name')->notNull();
    $table->integer('age')->size('small')->notNull();
    $table->string('gender', 1)->notNull();
});

$data = json_decode(file_get_contents(__DIR__ . '/data.json'), true);

foreach ($data as $item) {
    $db->insert($item)->into('users');
}

unset($data);

\Opis\ORM\Test\entityManager(new EntityManager($connection));

