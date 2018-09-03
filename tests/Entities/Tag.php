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

namespace Opis\ORM\Test\Entities;

use Opis\ORM\{
    Core\DataMapper, Core\EntityMapper, Entity, IDataMapper, IMappableEntity, IEntityMapper
};

class Tag extends Entity implements IMappableEntity
{
    private $event = '';

    public function name(): string
    {
        return $this->orm()->getColumn('id');
    }

    public function setName(string $name)
    {
        if ($this->orm()->isNew()) {
            $this->orm()->setColumn('id', $name);
        }
    }

    /**
     * @return Article[]
     */
    public function articles(): array
    {
        return $this->orm()->getRelated('articles');
    }

    public function getEventName(): string
    {
        return $this->event;
    }

    /**
     * @inheritDoc
     */
    public static function mapEntity(IEntityMapper $mapper)
    {
        $mapper->primaryKeyGenerator(function(DataMapper $data){
            return $data->getColumn('id');
        });

        $mapper->relation('articles')->shareMany(Article::class);

        $mapper->on('save', function(Tag $tag, IDataMapper $dataMapper){
            $tag->event = 'save';
        });

        $mapper->on('delete', function(Tag $tag, IDataMapper $dataMapper){
            $tag->event = 'delete';
        });
    }

}