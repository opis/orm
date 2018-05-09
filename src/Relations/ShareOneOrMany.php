<?php
/* ===========================================================================
 * Copyright 2013-2018 The Opis Project
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
    DataMapper, EntityMapper, EntityQuery, ForeignKey, Junction, LazyLoader, Proxy, Query, Relation
};

class ShareOneOrMany extends Relation
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
     * @param EntityManager $manager
     * @param EntityMapper $owner
     * @param array $options
     * @return LazyLoader
     */
    public function getLazyLoader(EntityManager $manager, EntityMapper $owner, array $options)
    {
        $related = $manager->resolveEntityMapper($this->entityClass);

        if ($this->junction === null) {
            $this->junction = $this->buildJunction($owner, $related);
        }

        if($this->foreignKey === null){
            $this->foreignKey = $owner->getForeignKey();
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

        if($this->joinTable === null){
            $this->joinTable = $related->getTable();
        }

        if($this->joinColumn === null){
            $this->joinColumn = $related->getPrimaryKey();
        }

        $ids = [];
        foreach ($options['results'] as $result) {
            foreach ($owner->getPrimaryKey()->getValue($result, true) as $pk_col => $pk_val) {
                $ids[$pk_col][] = $pk_val;
            }
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

        foreach ($this->foreignKey->getValue($data->getRawColumns(), true) as $fk_column => $value) {
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
        return new class($owner, $related) extends Junction {
            public function __construct(EntityMapper $owner, EntityMapper $related)
            {
                $table = [$owner->getTable(), $related->getTable()];
                sort($table);
                parent::__construct(implode('_', $table), $related->getForeignKey()->columns());
            }
        };
    }
}