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

namespace Opis\ORM;

use Opis\ORM\Core\RelationFactory;

interface IEntityMapper
{
    /**
     * @param string $class
     * @return IEntityMapper
     */
    public function entityClass(string $class): self;

    /**
     * @param string $table
     * @return IEntityMapper
     */
    public function table(string $table): self;

    /**
     * @param string ...$primaryKey
     * @return IEntityMapper
     */
    public function primaryKey(string ...$primaryKey): self;

    /**
     * @param callable $callback
     * @return IEntityMapper
     */
    public function primaryKeyGenerator(callable $callback): self;

    /**
     * @param string $sequence
     * @return IEntityMapper
     */
    public function sequence(string $sequence): self;

    /**
     * @param string $column
     * @param callable $callback
     * @return IEntityMapper
     */
    public function getter(string $column, callable $callback): self;

    /**
     * @param string $column
     * @param callable $callback
     * @return IEntityMapper
     */
    public function setter(string $column, callable $callback): self;

    /**
     * @param string $name
     * @return RelationFactory
     */
    public function relation(string $name): RelationFactory;

    /**
     * @param array $casts
     * @return IEntityMapper
     */
    public function cast(array $casts): self;

    /**
     * @param bool $value
     * @return IEntityMapper
     */
    public function useSoftDelete(bool $value = true): self;

    /**
     * @param bool $value
     * @return IEntityMapper
     */
    public function useTimestamp(bool $value = true): self;

    /**
     * @param string[] $columns
     * @return IEntityMapper
     */
    public function fillable(array $columns): self;

    /**
     * @param string[] $columns
     * @return IEntityMapper
     */
    public function guarded(array $columns): self;

    /**
     * @param string $name
     * @param callable $callback
     * @return IEntityMapper
     */
    public function filter(string $name, callable $callback): self;
}