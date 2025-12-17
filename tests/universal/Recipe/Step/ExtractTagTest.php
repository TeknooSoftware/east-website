<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the 3-Clause BSD license
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
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Recipe\Step;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Loader\TagLoader;
use Teknoo\East\Website\Recipe\Step\ExtractTag;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ExtractTag::class)]
class ExtractTagTest extends TestCase
{
    private (TagLoader&Stub)|(TagLoader&MockObject)|null $tagLoader = null;

    private function getTagLoader(bool $stub = false): (TagLoader&Stub)|(TagLoader&MockObject)
    {
        if (!$this->tagLoader instanceof TagLoader) {
            if ($stub) {
                $this->tagLoader = $this->createStub(TagLoader::class);
            } else {
                $this->tagLoader = $this->createMock(TagLoader::class);
            }
        }

        return $this->tagLoader;
    }

    private function getStep(): ExtractTag
    {
        return new ExtractTag($this->getTagLoader(true));
    }

    public function testInvoke(): void
    {
        $this->assertInstanceOf(ExtractTag::class, $this->getStep()(
            $this->createStub(ManagerInterface::class),
            'foo',
        ));
    }
}
