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

class DeleteTest extends TestCase
{
    public function testDelete()
    {
        /** @var Tag $tag */
        $tag = entity(Tag::class)->find('foo');
        $this->assertTrue(em()->delete($tag));
        $this->assertNull(entity(Tag::class)->find('foo'));
    }
}