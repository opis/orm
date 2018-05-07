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

namespace Opis\ORM\Core;

use Opis\Database\Connection;
use Opis\Database\SQL\{Delete, SQLStatement, Update};
use Opis\ORM\{Entity, EntityManager};
use Opis\ORM\Traits\AggregateTrait;

class EntityQuery extends Query
{
    use AggregateTrait;

    /** @var EntityManager */
    protected $manager;

    /** @var EntityMapper */
    protected $mapper;

    /** @var bool */
    protected $locked = false;

    /** @var null|string[] */
    protected $includedColumns;

    /**
     * EntityQuery constructor.
     * @param EntityManager $entityManager
     * @param EntityMapper $entityMapper
     * @param SQLStatement|null $statement
     */
    public function __construct(EntityManager $entityManager, EntityMapper $entityMapper, SQLStatement $statement = null)
    {
        parent::__construct($statement);
        $this->mapper = $entityMapper;
        $this->manager = $entityManager;
    }

    /**
     * @param string ...$columns
     * @return EntityQuery
     */
    public function includeColumns(string ...$columns): self
    {
        $this->includedColumns = $columns;
        return $this;
    }

    /**
     * @param string|string[]|mixed[] $names
     * @return EntityQuery
     */
    public function filter($names): self
    {
        if(!is_array($names)){
            $names = [$names];
        }

        $query = new Query($this->sql);
        $filters = $this->mapper->getFilters();

        foreach ($names as $name => $data){
            if (is_int($name)) {
                $name = $data;
                $data = null;
            }
            if(isset($filters[$name])){
                $filters[$name]($query, $data);
            }
        }

        return $this;
    }

    /**
     * @return null|Entity
     */
    public function get()
    {
        $result = $this->query($this->includedColumns ?? [])
                       ->fetchAssoc()
                       ->first();

        if($result === false){
            return null;
        }

        $class = $this->mapper->getClass();

        return new $class($this->manager, $this->mapper, $result, [], $this->isReadOnly(), false);
    }

    /**
     * @return Entity[]
     */
    public function all(): array
    {
        $results = $this->query($this->includedColumns ?? [])
                         ->fetchAssoc()
                         ->all();
        
        $entities = [];

        $class = $this->mapper->getClass();
        $isReadOnly = $this->isReadOnly();
        $loaders = $this->getLazyLoaders($results);

        foreach ($results as $result){
            $entities[] = new $class($this->manager, $this->mapper, $result, $loaders, $isReadOnly, false);
        }

        return $entities;
    }

    /**
     * @param bool $force
     * @param array $tables
     * @return int
     * @throws \Exception
     */
    public function delete(bool $force = false, array $tables = [])
    {
        return $this->transaction(function (Connection $connection) use($tables, $force) {
            if(!$force && $this->mapper->supportsSoftDelete()){
                return (new Update($connection, $this->mapper->getTable(), $this->sql))->set([
                    'deleted_at' => date($this->manager->getDateFormat())
                ]);
            }
            return (new Delete($connection, $this->mapper->getTable(), $this->sql))->delete($tables);
        });
    }

    /**
     * @param array $columns
     * @return int
     * @throws \Exception
     */
    public function update(array $columns = [])
    {
        return $this->transaction(function (Connection $connection) use($columns) {
            if($this->mapper->supportsTimestamp()){
                $columns['updated_at'] = date($this->manager->getDateFormat());
            }
            return (new Update($connection, $this->mapper->getTable(), $this->sql))->set($columns);
        });
    }

    /**
     * @param string[]|string $column
     * @param int $value
     * @return int
     * @throws \Exception
     */
    public function increment($column, $value = 1)
    {
        return $this->transaction(function (Connection $connection) use($column, $value) {
            if($this->mapper->supportsTimestamp()){
                $this->sql->addUpdateColumns([
                    'updated_at' => date($this->manager->getDateFormat())
                ]);
            }
            return (new Update($connection, $this->mapper->getTable(), $this->sql))->increment($column, $value);
        });
    }

    /**
     * @param string[]|string $column
     * @param int $value
     * @return int
     * @throws \Exception
     */
    public function decrement($column, $value = 1)
    {
        return $this->transaction(function(Connection $connection) use($column, $value) {
            if($this->mapper->supportsTimestamp()){
                $this->sql->addUpdateColumns([
                    'updated_at' => date($this->manager->getDateFormat())
                ]);
            }
            return (new Update($connection, $this->mapper->getTable(), $this->sql))->decrement($column, $value);
        });
    }

    /**
     * @param $id
     * @return mixed|null
     */
    public function find($id)
    {
        if (is_array($id)) {
            foreach ($id as $pk_column => $pk_value) {
                $this->where($pk_column)->is($pk_value);
            }
        } else {
            $this->where($this->mapper->getPrimaryKey())->is($id);
        }

        return $this->get();
    }

    /**
     * @param array $ids
     * @param array $columns
     * @return array
     */
    public function findAll(array $ids, array $columns = []): array
    {
        return $this->where($this->mapper->getPrimaryKey())->in($ids)
                    ->all($columns);
    }

    /**
     * @param \Closure $callback
     * @param int $default
     * @return int
     * @throws \Exception
     */
    protected function transaction(\Closure $callback, $default = 0)
    {
        return $this->manager->getConnection()->transaction($callback, null, $default);
    }

    /**
     * @return EntityQuery
     */
    protected function buildQuery()
    {
        $this->sql->addTables([$this->mapper->getTable()]);
        return $this;
    }

    /**
     * @param array $columns
     * @return \Opis\Database\ResultSet;
     */
    protected function query(array $columns)
    {
        if (!$this->buildQuery()->locked && !empty($columns)) {
            foreach ((array) $this->mapper->getPrimaryKey() as $pk_column) {
                $columns[] = $pk_column;
            }
        }

        if($this->mapper->supportsSoftDelete()){
            if(!$this->withSoftDeleted){
                $this->where('deleted_at')->isNull();
            } elseif ($this->onlySoftDeleted){
                $this->where('deleted_at')->notNull();
            }
        }

        $this->select($columns);

        $connection = $this->manager->getConnection();
        $compiler = $connection->getCompiler();

        return $connection->query($compiler->select($this->sql), $compiler->getParams());
    }
    
    /**
     * @return mixed
     */
    protected function executeAggregate()
    {
        $this->sql->addTables([$this->mapper->getTable()]);

        if($this->mapper->supportsSoftDelete()){
            if(!$this->withSoftDeleted){
                $this->where('deleted_at')->isNull();
            } elseif ($this->onlySoftDeleted){
                $this->where('deleted_at')->notNull();
            }
        }

        $connection = $this->manager->getConnection();
        $compiler = $connection->getCompiler();

        return $connection->column($compiler->select($this->sql), $compiler->getParams());
    }


    /**
     * @return bool
     */
    protected function isReadOnly(): bool
    {
        return !empty($this->sql->getJoins());
    }

    /**
     * @param array $results
     * @return array
     */
    protected function getLazyLoaders(array $results): array
    {
        if(empty($this->with) || empty($results)){
            return [];
        }

        $loaders = [];
        $attr = $this->getWithAttributes();
        $relations = $this->mapper->getRelations();

        foreach ($attr['with'] as $with => $callback) {
            if(!isset($relations[$with])){
                continue;
            }

            $loader = RelationProxy::getRelationLazyLoader($relations[$with], $this->manager, $this->mapper,[
                'results' => $results,
                'callback' => $callback,
                'with' => $attr[$with]['extra'] ?? [],
                'immediate' => $this->immediate,
            ]);

            if(null === $loader){
                continue;
            }
            $loaders[$with] = $loader;
        }

        return $loaders;
    }
}
