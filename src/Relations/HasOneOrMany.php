<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

use Opis\Database\SQL\SQLStatement;
use Opis\ORM\{
    Entity, EntityManager
};
use Opis\ORM\Core\{
    DataMapper, EntityMapper, EntityQuery, ForeignKey, LazyLoader, Proxy, Relation, Query
};

class HasOneOrMany extends Relation
{
    /** @var bool */
    protected $hasMany;

    /**
     * EntityHasOneOrMany constructor.
     * @param string $entityClass
     * @param ForeignKey|null $foreignKey
     * @param bool $hasMany
     */
    public function __construct(string $entityClass, ForeignKey $foreignKey = null, bool $hasMany = false)
    {
        parent::__construct($entityClass, $foreignKey);
        $this->hasMany = $hasMany;
    }

    /**
     * @param DataMapper $owner
     * @param Entity $entity
     */
    public function addRelatedEntity(DataMapper $owner, Entity $entity)
    {
        $mapper = $owner->getEntityMapper();

        if ($this->foreignKey === null) {
            $this->foreignKey = $mapper->getForeignKey();
        }

        $related = Proxy::instance()->getDataMapper($entity);

        foreach ($this->foreignKey->getValue($owner->getRawColumns(), true) as $fk_column => $fk_value) {
            $related->setColumn($fk_column, $fk_value);
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

        if ($this->foreignKey === null) {
            $this->foreignKey = $owner->getForeignKey();
        }

        $ids = [];
        $pk = $owner->getPrimaryKey();

        foreach ($options['results'] as $result) {
            foreach ($pk->getValue($result, true) as $pk_col => $pk_val) {
                $ids[$pk_col][] = $pk_val;
            }
        }

        $statement = new SQLStatement();
        $select = new EntityQuery($manager, $related, $statement);

        foreach ($this->foreignKey->getValue($ids, true) as $fk_col => $fk_val) {
            $select->where($fk_col)->in($fk_val);
        }


        if ($options['callback'] !== null) {
            $options['callback'](new Query($statement));
        }

        $select->with($options['with'], $options['immediate']);

        return new LazyLoader($select, $this->foreignKey, false, $this->hasMany, $options['immediate']);
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

        if ($this->foreignKey === null) {
            $this->foreignKey = $owner->getForeignKey();
        }

        $statement = new SQLStatement();
        $select = new EntityQuery($manager, $related, $statement);

        foreach ($this->foreignKey->getValue($data->getRawColumns(), true) as $fk_column => $fk_value) {
            $select->where($fk_column)->is($fk_value);
        }

        if ($this->queryCallback !== null || $callback !== null) {
            $query = $select;//new Query($statement);
            if ($this->queryCallback !== null) {
                ($this->queryCallback)($query);
            }
            if ($callback !== null) {
                $callback($query);
            }
        }

        return $this->hasMany ? $select->all() : $select->get();
    }
}