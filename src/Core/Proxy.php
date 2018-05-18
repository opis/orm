<?php
/* ===========================================================================
 * Copyright 2018 The Opis Project
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

use ReflectionClass;
use ReflectionProperty;
use Opis\ORM\Entity;

class Proxy
{
    /** @var ReflectionProperty */
    private $dataMapperArgs;

    /** @var \ReflectionMethod */
    private $ormMethod;

    /** @var \ReflectionMethod */
    private $relationGetResult;

    /** @var \ReflectionMethod */
    private $relationGetLazyLoader;

    /** @var \ReflectionMethod */
    private $markAsSaved;

    /** @var \ReflectionMethod */
    private $markAsUpdated;

    /** @var \ReflectionMethod */
    private $markAsDeleted;

    /** @var \ReflectionMethod */
    private $executePendingLinkage;

    /**
     * Proxy constructor.
     * @throws \ReflectionException
     */
    private function __construct()
    {
        $entityReflection = new ReflectionClass(Entity::class);
        $relationReflection = new ReflectionClass(Relation::class);
        $dataMapperReflection = new ReflectionClass(DataMapper::class);

        $this->dataMapperArgs = $entityReflection->getProperty('dataMapperArgs');
        $this->ormMethod = $entityReflection->getMethod('orm');
        $this->relationGetResult = $relationReflection->getMethod('getResult');
        $this->relationGetLazyLoader = $relationReflection->getMethod('getLazyLoader');
        $this->markAsSaved = $dataMapperReflection->getMethod('markAsSaved');
        $this->markAsUpdated = $dataMapperReflection->getMethod('markAsUpdated');
        $this->markAsDeleted = $dataMapperReflection->getMethod('markAsDeleted');
        $this->executePendingLinkage = $dataMapperReflection->getMethod('executePendingLinkage');
        $this->dataMapperArgs->setAccessible(true);
        $this->ormMethod->setAccessible(true);
        $this->relationGetResult->setAccessible(true);
        $this->relationGetLazyLoader->setAccessible(true);
        $this->markAsSaved->setAccessible(true);
        $this->markAsUpdated->setAccessible(true);
        $this->markAsDeleted->setAccessible(true);
        $this->executePendingLinkage->setAccessible(true);
    }

    /**
     * @param Entity $entity
     * @return DataMapper
     */
    public function getDataMapper(Entity $entity): DataMapper
    {
        return $this->ormMethod->invoke($entity);
    }

    /**
     * @param Entity $entity
     * @return array
     */
    public function getEntityColumns(Entity $entity): array
    {
        if (null !== $value = $this->dataMapperArgs->getValue($entity)) {
            return $value[2];
        }

        return $this->getDataMapper($entity)->getRawColumns();
    }

    /**
     * @return Proxy
     */
    public static function instance(): Proxy
    {
        static $proxy;
        if ($proxy === null) {
            try {
                $proxy = new self();
            } catch (\ReflectionException $exception) {

            }
        }
        return $proxy;
    }
}