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

namespace Opis\ORM\Relations;

use Opis\ORM\Entity;
use Opis\ORM\EntityManager;
use Opis\Database\SQL\{Delete, Insert, Join, SQLStatement};
use Opis\ORM\Core\{
    DataMapper, EntityMapper, EntityQuery, ForeignKey, Junction, LazyLoader, Proxy, Query, Relation, EntityProxy
};

class HasOneOrManyThrough extends Relation
{
    /** @var null|Junction */
    protected $junction;

    /** @var  bool */
    protected $hasMany;

    public function __construct(string $entityClass,
                                ForeignKey $foreignKey = null,
                                Junction $junction = null,
                                bool $hasMany = false)
    {
        parent::__construct($entityClass, $foreignKey);
        $this->hasMany = $hasMany;
        $this->junction = $junction;
    }

    /**
     * @param DataMapper $data
     * @param $items
     */
    public function link(DataMapper $data, $items)
    {
        if(!is_array($items)){
            $items = [$items];
        }

        $manager = $data->getEntityManager();
        $owner = $data->getEntityMapper();
        $related = $manager->resolveEntityMapper($this->entityClass);

        if ($this->junction === null) {
            $this->junction = $this->buildJunction($owner, $related);
        }

        if($this->junctionTable === null){
            $table = [$owner->getTable(), $related->getTable()];
            sort($table);
            $this->junctionTable = implode('_', $table);
        }

        if($this->junctionKey === null){
            $this->junctionKey = $related->getForeignKey();
        }

        if($this->foreignKey === null){
            $this->foreignKey = $owner->getForeignKey();
        }

        $table = $this->junctionTable;
        $col1 = $this->foreignKey;
        $col2 = $this->junctionKey;
        $val1 = $data->getColumn($owner->getPrimaryKey());
        $key = $related->getPrimaryKey();
        $connection = $manager->getConnection();

        foreach ($items as $item){
            $val2 = is_a($item, $this->entityClass, false)
                ? EntityProxy::getDataMapper($item)->getColumn($key)
                : $item;
            try{

                (new Insert($connection))->insert([
                    $col1 => $val1,
                    $col2 => $val2
                ])->into($table);

            }catch (\Exception $e){
                // Ignore
            }
        }
    }

    public function linkEntity(DataMapper $data, Entity $entity): bool
    {
        $manager = $data->getEntityManager();
        $owner = $data->getEntityMapper();
        $related = $manager->resolveEntityMapper($this->entityClass);

        if ($this->junction === null) {
            $this->junction = $this->buildJunction($owner, $related);
        }

        if($this->foreignKey === null){
            $this->foreignKey = $owner->getForeignKey();
        }

        $values = [];

        foreach ($this->foreignKey->getValue($data->getColumns(), true) as $fk_column => $fk_value) {
            $values[$fk_column] = $fk_value;
        }

        $columns = Proxy::instance()->getDataMapper($entity)->getColumns();
        foreach ($this->junction->columns() as $pk_column => $fk_column) {
            $values[$fk_column] = $columns[$pk_column];
        }

        $cmd = new Insert($manager->getConnection());
        $cmd->insert($values);

        return (bool) $cmd->into($this->junction->table());
    }

    public function unlinkEntity(DataMapper $data, Entity $entity): bool
    {
        $manager = $data->getEntityManager();
        $owner = $data->getEntityMapper();
        $related = $manager->resolveEntityMapper($this->entityClass);

        if ($this->junction === null) {
            $this->junction = $this->buildJunction($owner, $related);
        }

        if($this->foreignKey === null){
            $this->foreignKey = $owner->getForeignKey();
        }

        $values = [];

        foreach ($this->foreignKey->getValue($data->getColumns(), true) as $fk_column => $fk_value) {
            $values[$fk_column] = $fk_value;
        }

        $columns = Proxy::instance()->getDataMapper($entity)->getColumns();
        foreach ($this->junction->columns() as $pk_column => $fk_column) {
            $values[$fk_column] = $columns[$pk_column];
        }

        $cmd = new Delete($manager->getConnection(), $this->junction->table());

        foreach ($values as $column => $value) {
            $cmd->where($column)->is($value);
        }

        return (bool) $cmd->delete();
    }

    /**
     * @param DataMapper $data
     * @param $items
     */
    public function unlink(DataMapper $data, $items)
    {
        if(!is_array($items)){
            $items = [$items];
        }

        $manager = $data->getEntityManager();
        $owner = $data->getEntityMapper();
        $related = $manager->resolveEntityMapper($this->entityClass);

        if($this->junctionTable === null){
            $table = [$owner->getTable(), $related->getTable()];
            sort($table);
            $this->junctionTable = implode('_', $table);
        }

        if($this->junctionKey === null){
            $this->junctionKey = $related->getForeignKey();
        }

        if($this->foreignKey === null){
            $this->foreignKey = $owner->getForeignKey();
        }

        $table = $this->junctionTable;
        $col1 = $this->foreignKey;
        $col2 = $this->junctionKey;
        $val1 = $data->getColumn($owner->getPrimaryKey());
        $val2 = [];
        $key = $related->getPrimaryKey();
        $connection = $manager->getConnection();

        foreach ($items as $item){
            $val2[] = is_a($item, $this->entityClass, false)
                ? EntityProxy::getDataMapper($item)->getColumn($key)
                : $item;
        }

        try{
            (new Delete($connection, $table))
                ->where($col1)->is($val1)
                ->andWhere($col2)->in($val2)
                ->delete();
        }
        catch(\Exception $e){
            //ignore
        }
    }


    /**
     * @param EntityManager $manager
     * @param EntityMapper $owner
     * @param array $options
     * @return LazyLoader
     */
    public function getLazyLoader(EntityManager $manager, EntityMapper $owner, array $options)
    {
        $related = $manager->resolveEntityMapper($this->entityClass);

        if($this->junctionTable === null){
            $table = [$owner->getTable(), $related->getTable()];
            sort($table);
            $this->junctionTable = implode('_', $table);
        }

        if($this->junctionKey === null){
            $this->junctionKey = $related->getForeignKey();
        }

        if($this->foreignKey === null){
            $this->foreignKey = $owner->getForeignKey();
        }

        if($this->joinTable === null){
            $this->joinTable = $related->getTable();
        }

        if($this->joinColumn === null){
            $this->joinColumn = $related->getPrimaryKey();
        }

        $ids = [];
        $pk = $owner->getPrimaryKey();
        foreach ($options['results'] as $result){
            $ids[] = $result[$pk];
        }

        $statement = new SQLStatement();

        $select = new class($manager, $related, $statement, $this->junctionTable) extends EntityQuery{

            protected $junctionTable;

            public function __construct(EntityManager $entityManager, EntityMapper $entityMapper, $statement, $table)
            {
                parent::__construct($entityManager, $entityMapper, $statement);
                $this->junctionTable = $table;
            }

            protected function buildQuery(): EntityQuery
            {
                $this->locked = true;
                $this->sql->addTables([$this->junctionTable]);
                return $this;
            }

            protected function isReadOnly(): bool
            {
                return count($this->sql->getJoins()) > 1;
            }
        };

        $linkKey = 'hidden_' . $this->junctionTable . '_' . $this->foreignKey;

        $select->join($this->joinTable, function (Join $join){
            $join->on($this->junctionTable . '.' . $this->junctionKey, $this->joinTable . '.' . $this->joinColumn);
        })
            ->where($this->junctionTable . '.' . $this->foreignKey)->in($ids);

        $statement->addColumn($this->joinTable . '.*');
        $statement->addColumn($this->junctionTable . '.' . $this->foreignKey, $linkKey);

        if($options['callback'] !== null){
            $options['callback'](new Query($statement));
        }

        $select->with($options['with'], $options['immediate']);

        return new LazyLoader($select, $pk, $linkKey, $this->hasMany, $options['immediate']);
    }

    /**
     * @param DataMapper $data
     * @param callable|null $callback
     * @return mixed
     */
    public function getResult(DataMapper $data, callable $callback = null)
    {
        $manager = $data->getEntityManager();
        $owner = $data->getEntityMapper();
        $related = $manager->resolveEntityMapper($this->entityClass);

        if ($this->junction === null) {
            $this->junction = $this->buildJunction($owner, $related);
        }

        if($this->foreignKey === null){
            $this->foreignKey = $owner->getForeignKey();
        }

        $junctionTable = $this->junction->table();
        $joinTable = $related->getTable();

        $statement = new SQLStatement();

        $select = new class($manager, $related, $statement, $junctionTable) extends EntityQuery{

            protected $junctionTable;

            public function __construct(EntityManager $entityManager, EntityMapper $entityMapper, $statement, $table)
            {
                parent::__construct($entityManager, $entityMapper, $statement);
                $this->junctionTable = $table;
            }

            protected function buildQuery(): EntityQuery
            {
                $this->locked = true;
                $this->sql->addTables([$this->junctionTable]);
                return $this;
            }
            
            protected function isReadOnly(): bool
            {
                return count($this->sql->getJoins()) > 1;
            }
        };

        $select->join($joinTable, function (Join $join) use($junctionTable, $joinTable){
            foreach ($this->junction->columns() as $pk_column => $fk_column) {
                $join->on($junctionTable . '.' . $fk_column, $joinTable . '.' . $pk_column);
            }
        });

        foreach ($this->foreignKey->getValue($data->getColumns(), true) as $fk_column => $value) {
            $select->where($junctionTable . '.' . $fk_column)->is($value);
        }

        $statement->addColumn($joinTable . '.*');

        if($this->queryCallback !== null || $callback !== null){
            $query = $select;//new Query($statement);
            if($this->queryCallback !== null){
                ($this->queryCallback)($query);
            }
            if($callback !== null){
                $callback($query);
            }
        }

        return $this->hasMany ? $select->all() : $select->get();
    }

    /**
     * @param EntityMapper $owner
     * @param EntityMapper $related
     * @return Junction
     */
    protected function buildJunction(EntityMapper $owner,EntityMapper $related): Junction
    {
        return new class extends Junction {
            public function __construct(EntityMapper $owner, EntityMapper $related)
            {
                $table = [$owner->getTable(), $related->getTable()];
                sort($table);
                parent::__construct(implode('_', $table), $related->getForeignKey()->columns());
            }
        };
    }
}