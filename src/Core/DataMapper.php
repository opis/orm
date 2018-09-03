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

namespace Opis\ORM\Core;

use DateTime;
use RuntimeException;
use Opis\Database\SQL\Select;
use Opis\ORM\{
    Entity, EntityManager, IDataMapper
};
use Opis\ORM\Relations\{
    BelongsTo, HasOneOrMany, ShareOneOrMany
};

class DataMapper implements IDataMapper
{
    /** @var array */
    protected $rawColumns;

    /** @var array */
    protected $columns = [];

    /** @var  LazyLoader[] */
    protected $loaders;

    /** @var EntityManager */
    protected $manager;

    /** @var EntityMapper */
    protected $mapper;

    /** @var bool */
    protected $isReadOnly;

    /** @var bool */
    protected $isNew;

    /** @var  string|null */
    protected $sequence;

    /** @var array */
    protected $modified = [];

    /** @var array */
    protected $relations = [];

    /** @var bool */
    protected $stale = false;

    /** @var bool */
    protected $deleted = false;

    /** @var array */
    protected $pendingLinks = [];

    /**
     * DataMapper constructor.
     * @param EntityManager $entityManager
     * @param EntityMapper $entityMapper
     * @param array $columns
     * @param LazyLoader[] $loaders
     * @param bool $isReadOnly
     * @param bool $isNew
     */
    public function __construct(
        EntityManager $entityManager,
        EntityMapper $entityMapper,
        array $columns,
        array $loaders,
        bool $isReadOnly,
        bool $isNew
    ) {
        $this->manager = $entityManager;
        $this->mapper = $entityMapper;
        $this->loaders = $loaders;
        $this->isReadOnly = $isReadOnly;
        $this->isNew = $isNew;
        $this->rawColumns = $columns;

        if ($isNew && !empty($columns)) {
            $this->rawColumns = [];
            $this->assign($columns);
        }
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager(): EntityManager
    {
        return $this->manager;
    }

    /**
     * @return EntityMapper
     */
    public function getEntityMapper(): EntityMapper
    {
        return $this->mapper;
    }

    /**
     * @return bool
     */
    public function isNew(): bool
    {
        return $this->isNew;
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->isReadOnly;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @return bool
     */
    public function wasModified(): bool
    {
        return !empty($this->modified) || !empty($this->pendingLinks);
    }

    /**
     * @return array
     */
    public function getRawColumns(): array
    {
        return $this->rawColumns;
    }

    /**
     * @return string[]
     */
    public function getModifiedColumns(): array
    {
        return array_keys($this->modified);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function getColumn(string $name)
    {
        if ($this->stale) {
            $this->hydrate();
        }

        if ($this->deleted) {
            throw new RuntimeException("The record was deleted");
        }

        if (array_key_exists($name, $this->columns)) {
            return $this->columns[$name];
        }

        if (!array_key_exists($name, $this->rawColumns)) {
            throw new RuntimeException("Unknown column '$name'");
        }

        $value = $this->rawColumns[$name];
        $casts = $this->mapper->getTypeCasts();

        if (isset($casts[$name])) {
            $value = $this->castGet($value, $casts[$name]);
        }

        if ($name === (string)$this->mapper->getPrimaryKey()) {
            return $this->columns[$name] = $value;
        }

        $getters = $this->mapper->getGetters();

        if (isset($getters[$name])) {
            $value = $getters[$name]($value, $this);
        }

        return $this->columns[$name] = $value;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function setColumn(string $name, $value)
    {
        if ($this->isReadOnly) {
            throw new RuntimeException("The record is readonly");
        }

        if ($this->deleted) {
            throw new RuntimeException("The record was deleted");
        }

        if ($this->stale) {
            $this->hydrate();
        }

        $casts = $this->mapper->getTypeCasts();
        $setters = $this->mapper->getSetters();

        if (isset($setters[$name])) {
            $value = $setters[$name]($value, $this);
        }

        if (isset($casts[$name])) {
            $value = $this->castSet($value, $casts[$name]);
        }

        $this->modified[$name] = 1;
        unset($this->columns[$name]);
        $this->rawColumns[$name] = $value;
    }

    /**
     * @param string $name
     */
    public function clearColumn(string $name)
    {
        unset($this->columns[$name]);
    }

    /**
     * @param string $name
     * @param $value
     */
    public function setRawColumn(string $name, $value)
    {
        $this->modified[$name] = 1;
        unset($this->columns[$name]);
        $this->rawColumns[$name] = $value;
    }

    /**
     * @param string $name
     * @param callable|null $callback
     * @return mixed
     */
    public function getRelated(string $name, callable $callback = null)
    {
        if (array_key_exists($name, $this->relations)) {
            return $this->relations[$name];
        }

        $relations = $this->mapper->getRelations();

        $cache_key = $name;

        if (false !== $index = strpos($name, ':')) {
            $name = substr($name, $index + 1);
        }

        if (!isset($relations[$name])) {
            throw new RuntimeException("Unknown relation '$name'");
        }

        $this->hydrate();

        if (isset($this->relations[$cache_key])) {
            return $this->relations[$cache_key];
        }

        if (isset($this->loaders[$cache_key])) {
            return $this->relations[$cache_key] = $this->loaders[$name]->getResult($this);
        }

        return $this->relations[$cache_key] = $relations[$name]->getResult($this, $callback);
    }

    /**
     * @param string $relation
     * @param Entity|null $entity
     */
    public function setRelated(string $relation, Entity $entity = null)
    {
        $relations = $this->mapper->getRelations();

        if (!isset($relations[$relation])) {
            throw new RuntimeException("Unknown relation '$relation'");
        }

        $rel = $relations[$relation];

        /** @var $rel BelongsTo|HasOneOrMany */
        if (!($rel instanceof BelongsTo) && !($rel instanceof HasOneOrMany)) {
            throw new RuntimeException("Unsupported relation type");
        }

        if ($entity === null && !($rel instanceof BelongsTo)) {
            throw new RuntimeException("Unsupported relation type");
        }

        $rel->addRelatedEntity($this, $entity);
    }

    /**
     * @param string $name
     * @param bool $loaders
     */
    public function clearRelated(string $name, bool $loaders = false)
    {
        $cache_key = $name;

        if (false !== $index = strpos($name, ':')) {
            $name = substr($name, $index + 1);
        }

        unset($this->relations[$cache_key]);

        if ($loaders) {
            unset($this->loaders[$name]);
        }
    }

    /**
     * @param string $relation
     * @param Entity $entity
     */
    public function link(string $relation, Entity $entity)
    {
        $this->linkOrUnlink($relation, $entity, true);
    }

    /**
     * @param string $relation
     * @param Entity $entity
     */
    public function unlink(string $relation, Entity $entity)
    {
        $this->linkOrUnlink($relation, $entity, false);
    }

    /**
     * @param array $columns
     */
    public function assign(array $columns)
    {
        if (null !== $assignableColumns = $this->mapper->getAssignableColumns()) {
            $columns = array_intersect_key($columns, array_flip($assignableColumns));
        } elseif (null !== $guarded = $this->mapper->getGuardedColumns()) {
            $columns = array_diff_key($columns, array_flip($guarded));
        }
        foreach ($columns as $name => $value) {
            $this->setColumn($name, $value);
        }
    }

    /**
     * Force hydration
     */
    public function stale()
    {
        $this->stale = true;
    }

    /**
     * @param $id
     * @return bool
     */
    public function markAsSaved($id): bool
    {
        $pk = $this->mapper->getPrimaryKey();

        if (!$pk->isComposite()) {
            $this->rawColumns[$pk->columns()[0]] = $id;
        } else {
            foreach ($pk->columns() as $pk_column) {
                $this->rawColumns[$pk_column] = $id[$pk_column];
            }
        }

        $this->stale = true;
        $this->isNew = false;
        $this->modified = [];
        if (!empty($this->pendingLinks)) {
            $this->executePendingLinkage();
        }
        return true;
    }

    /**
     * @param string|null $updatedAt
     * @return bool
     */
    public function markAsUpdated(string $updatedAt = null): bool
    {
        if ($updatedAt !== null) {
            $col = $this->mapper->getTimestampColumns()[1];
            unset($this->columns[$col]);
            $this->rawColumns[$col] = $updatedAt;
        }
        $this->modified = [];
        if (!empty($this->pendingLinks)) {
            $this->executePendingLinkage();
        }
        return true;
    }

    /**
     * @return bool
     */
    public function markAsDeleted(): bool
    {
        return $this->deleted = true;
    }

    /**
     * Execute pending linkage
     */
    public function executePendingLinkage()
    {
        foreach ($this->pendingLinks as $item) {
            /** @var ShareOneOrMany $rel */
            $rel = $item['relation'];
            if ($item['link']) {
                $rel->link($this, $item['entity']);
            } else {
                $rel->unlink($this, $item['entity']);
            }
        }

        $this->pendingLinks = [];
    }

    /**
     * @param $value
     * @param string $cast
     * @return mixed
     */
    protected function castGet($value, string $cast)
    {
        $originalCast = $cast;

        if ($cast[0] === '?') {
            if ($value === null) {
                return null;
            }
            $cast = substr($cast, 1);
        }

        switch ($cast) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'bool':
            case 'boolean':
                return (bool)$value;
            case 'string':
                return (string)$value;
            case 'date':
                return DateTime::createFromFormat($this->manager->getDateFormat(), $value);
            case 'json':
                return json_decode($value);
            case 'json-assoc':
                return json_decode($value, true);
        }

        throw new RuntimeException("Invalid cast type '$originalCast'");
    }

    /**
     * @param $value
     * @param string $cast
     * @return float|int|string
     */
    protected function castSet($value, string $cast)
    {
        $originalCast = $cast;

        if ($cast[0] === '?') {
            if ($value === null) {
                return null;
            }
            $cast = substr($cast, 1);
        }

        switch ($cast) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'bool':
            case 'boolean':
                return (int)$value;
            case 'string':
                return (string)$value;
            case 'date':
                /** @var $value DateTime */
                return $value->format($this->manager->getDateFormat());
            case 'json':
            case 'json-assoc':
                return json_encode($value);
        }

        throw new RuntimeException("Invalid cast type '$originalCast'");
    }

    /**
     * Hydrate
     */
    protected function hydrate()
    {
        if (!$this->stale) {
            return;
        }

        $select = new Select($this->manager->getConnection(), $this->mapper->getTable());

        foreach ($this->mapper->getPrimaryKey()->getValue($this->rawColumns, true) as $pk_column => $pk_value) {
            $select->where($pk_column)->is($pk_value);
        }

        $columns = $select->select()->fetchAssoc()->first();

        if ($columns === false) {
            $this->deleted = true;
            return;
        }

        $this->rawColumns = $columns;
        $this->columns = [];
        $this->relations = [];
        $this->loaders = [];
        $this->stale = false;
    }

    /**
     * @param string $relation
     * @param Entity $entity
     * @param bool $link
     */
    private function linkOrUnlink(string $relation, Entity $entity, bool $link)
    {
        $relations = $this->mapper->getRelations();

        if (!isset($relations[$relation])) {
            throw new RuntimeException("Unknown relation '$relation'");
        }

        /** @var $rel ShareOneOrMany */
        if (!(($rel = $relations[$relation]) instanceof ShareOneOrMany)) {
            throw new RuntimeException("Unsupported relation type");
        }

        $this->pendingLinks[] = [
            'relation' => $rel,
            'entity' => $entity,
            'link' => $link,
        ];
    }
}