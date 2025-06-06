<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * it is available in LICENSE file at the root of this package
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        https://teknoo.software/east-collection/website Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Writer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Writer\TagWriter;
use Teknoo\East\Common\Contracts\Writer\WriterInterface;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(TagWriter::class)]
class TagWriterTest extends TestCase
{
    use PersistTestTrait;

    public function buildWriter(bool $preferRealDateOnUpdate = false,): WriterInterface
    {
        return new TagWriter($this->getObjectManager(), $this->getDatesServiceMock(), $preferRealDateOnUpdate);
    }

    public function getObject()
    {
        return new Tag();
    }
}
