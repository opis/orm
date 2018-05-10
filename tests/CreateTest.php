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

namespace Opis\ORM\Test;

use Opis\ORM\Test\Entities\Tag;
use function Opis\ORM\Test\{
    entityManager as em,
    query as entity
};
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    public function testInstantiate()
    {
        $tag = em()->create(Tag::class);
        $this->assertNotNull($tag);
        $this->assertInstanceOf(Tag::class, $tag);
    }

    public function testCreate()
    {
        $count = entity(Tag::class)->count();
        /** @var Tag $tag */
        $tag = em()->create(Tag::class);
        $tag->setName('tag3');
        $this->assertTrue(em()->save($tag));
        $this->assertEquals($count + 1, entity(Tag::class)->count());
    }

    public function testFailCreate()
    {
        /** @var Tag $tag */
        $tag = em()->create(Tag::class);
        $tag->setName('tag3');
        $this->assertFalse(em()->save($tag));
    }
}