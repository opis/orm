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

class ForeignKey
{
    /** @var bool */
    private $composite;

    /** @var null|string[] */
    private $columns;

    /**
     * ForeignKey constructor.
     * @param string[] $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
        $this->composite = count($columns) > 1;
    }

    /**
     * @return bool
     */
    public function isComposite(): bool
    {
        return $this->composite;
    }

    /**
     * @return string[]
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     * @param bool $map
     * @return array|mixed|null
     */
    public function getValue(array $columns, bool $map = false)
    {
        if (!$map && !$this->composite) {
            return $columns[array_keys($this->columns)[0]] ?? null;
        }

        $value = [];

        foreach ($this->columns as $candidate => $column) {
            $value[$column] = $columns[$candidate] ?? null;
        }

        return $value;
    }

    /**
     * @param array $columns
     * @param bool $map
     * @return array|mixed|null
     */
    public function getInverseValue(array $columns, bool $map = false)
    {
        if (!$map && !$this->composite) {
            return $columns[array_values($this->columns)[0]] ?? null;
        }

        $value = [];

        foreach ($this->columns as $candidate => $column) {
            $value[$candidate] = $columns[$column] ?? null;
        }

        return $value;
    }

    /**
     * @param array $columns
     * @param bool $map
     * @return array|mixed|null
     */
    public function extractValue(array $columns, bool $map = false)
    {
        if (!$map && !$this->composite) {
            return $columns[array_values($this->columns)[0]] ?? null;
        }

        $value = [];

        foreach ($this->columns as $column) {
            $value[$column] = $columns[$column];
        }

        return $value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode(', ', $this->columns);
    }
}