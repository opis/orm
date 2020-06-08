<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

use Opis\ORM\Core\{
    DataMapper, EntityMapper
};

abstract class Entity
{

    private ?array $dataMapperArgs;

    private ?DataMapper $dataMapper = null;

    /**
     * Entity constructor.
     * @param EntityManager $entityManager
     * @param EntityMapper $entityMapper
     * @param array $columns
     * @param array $loaders
     * @param bool $isReadOnly
     * @param bool $isNew
     */
    final public function __construct(
        EntityManager $entityManager,
        EntityMapper $entityMapper,
        array $columns = [],
        array $loaders = [],
        bool $isReadOnly = false,
        bool $isNew = false
    ) {
        $this->dataMapperArgs = [$entityManager, $entityMapper, $columns, $loaders, $isReadOnly, $isNew];
    }

    /**
     * @return IDataMapper
     */
    final protected function orm(): IDataMapper
    {
        if ($this->dataMapper === null) {
            $this->dataMapper = new DataMapper(...$this->dataMapperArgs);
            $this->dataMapperArgs = null;
        }

        return $this->dataMapper;
    }
}
