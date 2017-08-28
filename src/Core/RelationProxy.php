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

use Opis\ORM\EntityManager;

abstract class RelationProxy extends Relation
{
    /**
     * @param Relation $relation
     * @param DataMapper $data
     * @param callable|null $callback
     * @return mixed
     */
    public static function getRelationResult(Relation $relation, DataMapper $data, callable $callback = null)
    {
        return $relation->getResult($data, $callback);
    }

    /**
     * @param Relation $relation
     * @param EntityManager $manager
     * @param EntityMapper $owner
     * @param array $options
     * @return mixed
     */
    public static function getRelationLazyLoader(Relation $relation, EntityManager $manager, EntityMapper $owner, array $options)
    {
        return $relation->getLazyLoader($manager, $owner, $options);
    }
}