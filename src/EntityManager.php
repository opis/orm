<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\ORM;

use RuntimeException;
use Opis\Database\Connection;
use Opis\Database\SQL\{Compiler, Insert, Update};
use Opis\ORM\Core\{
    DataMapper, EntityMapper, EntityProxy, EntityQuery
};

class EntityManager
{
    /** @var Connection  */
    protected $connection;

    /** @var  Compiler */
    protected $compiler;

    /** @var  string */
    protected $dateFormat;

    /** @var EntityMapper[] */
    protected $entityMappers = [];

    /** @var callable[] */
    protected $entityMappersCallbacks;

    /**
     * EntityManager constructor.
     * @param Connection $connection
     * @param callable[] $callbacks
     */
    public function __construct(Connection $connection, array $callbacks = [])
    {
        $this->connection = $connection;
        $this->entityMappersCallbacks = $callbacks;
    }

    /**
     * @param string $entityClass
     * @return EntityQuery
     */
    public function __invoke(string $entityClass)
    {
        return $this->query($entityClass);
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return Compiler
     */
    public function getCompiler(): Compiler
    {
        if($this->compiler === null){
            $this->compiler = $this->connection->getCompiler();
        }

        return $this->compiler;
    }

    /**
     * @return string
     */
    public function getDateFormat(): string
    {
        if($this->dateFormat === null){
            $this->dateFormat = $this->getCompiler()->getDateFormat();
        }

        return $this->dateFormat;
    }

    /**
     * @param string $entityClass
     * @return EntityQuery
     */
    public function query(string $entityClass): EntityQuery
    {
        return new EntityQuery($this, $this->resolveEntityMapper($entityClass));
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function save(Entity $entity): bool
    {
        /** @var DataMapper $data */
        $data = EntityProxy::getDataMapper($entity);

        if($data->isDeleted()){
            throw new RuntimeException("The record is deleted");
        }

        if($data->isNew()) {

            $id = $this->connection->transaction(function (Connection $connection) use($data) {
                $columns = $data->getRawColumns();

                // TODO: Review this and remove it if necessary
                foreach ($columns as &$column){
                    if($column instanceof Entity){
                        $column = EntityProxy::getPKValue($column);
                    }
                }

                $mapper = $data->getEntityMapper();

                if(null !== $pkgen = $mapper->getPrimaryKeyGenerator()){
                    $pk_data = $pkgen($data);
                    if (is_array($pk_data)) {
                        foreach ($pk_data as $pk_column => $pk_value){
                            $columns[$pk_column] = $pk_value;
                        }
                    } else {
                        $columns[$mapper->getPrimaryKey()] = $pk_data;
                    }
                }

                if($mapper->supportsTimestamp()){
                    $columns['created_at'] = date($this->getDateFormat());
                    $columns['updated_at'] = null;
                }

                (new Insert($connection))->insert($columns)->into($mapper->getTable());

                if ($pkgen !== null && !is_array($pk_data)) {
                    return $pk_data ?? false;
                }

                return $connection->getPDO()->lastInsertId($mapper->getSequence());
            }, null, false);

            return $id !== false ? DataMapper::markAsSaved($data, $id) : false;
        }

        $modified = $data->getModifiedColumns(false);

        if(!empty($modified)){
            return $this->connection->transaction(function (Connection $connection) use($data, $modified) {
                $columns = array_intersect_key($data->getRawColumns(), $modified);

                // TODO: Review this and remove it if necessary
                foreach ($columns as &$column){
                    if($column instanceof Entity){
                        $column = EntityProxy::getPKValue($column);
                    }
                }

                $mapper = $data->getEntityMapper();

                $updatedAt = null;

                if($mapper->supportsTimestamp()){
                    $columns['updated_at'] = $updatedAt = date($this->getDateFormat());
                }

                DataMapper::markAsUpdated($data, $updatedAt);

                $pk = $mapper->getPrimaryKey();
                $update = new Update($connection, $mapper->getTable());

                if (is_string($pk)) {
                    $update->where($pk)->is($data->getColumn($pk));
                } else {
                    foreach ($pk as $pk_column) {
                        $update->where($pk_column)->is($data->getColumn($pk_column));
                    }
                }

                return (bool) $update->set($columns);
            }, null, false);
        }

        return true;
    }

    /**
     * @param string $class
     * @param array $columns
     * @return Entity
     */
    public function create(string $class, array $columns = []): Entity
    {
        return new $class($this, $this->resolveEntityMapper($class), $columns, [], false, true);
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function delete(Entity $entity): bool
    {
        return $this->connection->transaction(function() use($entity) {
            /** @var DataMapper $data */
            $data = EntityProxy::getDataMapper($entity);

            if($data->isDeleted()){
                throw new RuntimeException("The record was already deleted");
            }

            if($data->isNew()){
                throw new RuntimeException("Can't delete an unsaved entity");
            }

            DataMapper::markAsDeleted($data);

            $mapper = $data->getEntityMapper();
            $pk = $mapper->getPrimaryKey();

            $delete = new EntityQuery($this, $mapper);

            if (is_string($pk)) {
                $delete->where($pk)->is($data->getColumn($pk));
            } else {
                foreach ($pk as $pk_column) {
                    $delete->where($pk_column)->is($data->getColumn($pk_column));
                }
            }

            return (bool) $delete->delete();
        }, null,false);
    }

    /**
     * @param string $class
     * @return EntityMapper
     */
    public function resolveEntityMapper(string $class): EntityMapper
    {
        if(isset($this->entityMappers[$class])){
            return $this->entityMappers[$class];
        }

        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new RuntimeException("Reflection error for '$class'", 0, $e);
        }

        if(!$reflection->isSubclassOf(Entity::class)){
            throw new RuntimeException("The '$class' must extend " . Entity::class);
        }

        if(isset($this->entityMappersCallbacks[$class])){
           $callback = $this->entityMappersCallbacks[$class];
        } elseif ($reflection->implementsInterface(IEntityMapper::class)){
            $callback = $class . '::mapEntity';
        } else {
            $callback = null;
        }

        $entityMapper = new EntityMapper($class);

        if($callback !== null){
            $callback($entityMapper);
        }

        return $this->entityMappers[$class] = $entityMapper;
    }

    /**
     * @param string $class
     * @param callable $callback
     * @return EntityManager
     */
    public function registerEntityMapper(string $class, callable $callback): self
    {
        $this->entityMappersCallbacks[$class] = $callback;
        return $this;
    }
}