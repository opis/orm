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

namespace Opis\ORM;

interface IDataMapper
{
    /**
     * @return bool
     */
    public function isNew(): bool;

    /**
     * @return bool
     */
    public function isReadOnly(): bool;

    /**
     * @return bool
     */
    public function isDeleted(): bool;

    /**
     * @return bool
     */
    public function wasModified(): bool;

    /**
     * @return array
     */
    public function getRawColumns(): array;

    /**
     * @return string[]
     */
    public function getModifiedColumns(): array;

    /**
     * @param string $name
     * @return mixed
     */
    public function getColumn(string $name);

    /**
     * @param string $name
     * @param $value
     */
    public function setColumn(string $name, $value);

    /**
     * @param string $name
     */
    public function clearColumn(string $name);

    /**
     * @param string $name
     * @param $value
     */
    public function setRawColumn(string $name, $value);

    /**
     * @param string $name
     * @param callable|null $callback
     * @return mixed
     */
    public function getRelated(string $name, callable $callback = null);

    /**
     * @param string $relation
     * @param Entity|null $entity
     * @return mixed
     */
    public function setRelated(string $relation, Entity $entity = null);

    /**
     * @param string $name
     * @param bool $loaders
     */
    public function clearRelated(string $name, bool $loaders = false);

    /**
     * @param string $relation
     * @param Entity $entity
     */
    public function link(string $relation, Entity $entity);

    /**
     * @param string $relation
     * @param Entity $entity
     */
    public function unlink(string $relation, Entity $entity);

    /**
     * @param array $columns
     */
    public function assign(array $columns);

    /**
     * Force hydration
     */
    public function stale();
}