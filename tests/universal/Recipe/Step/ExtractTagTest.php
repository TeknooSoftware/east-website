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

namespace Teknoo\Tests\East\Website\Recipe\Step;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Loader\TagLoader;
use Teknoo\East\Website\Recipe\Step\ExtractTag;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ExtractTag::class)]
class ExtractTagTest extends TestCase
{
    private (TagLoader&MockObject)|null $tagLoader = null;

    private function getTagLoader(): (TagLoader&MockObject)|null
    {
        if (null === $this->tagLoader) {
            $this->tagLoader = $this->createMock(TagLoader::class);
        }

        return $this->tagLoader;
    }

    private function getStep(): ExtractTag
    {
        return new ExtractTag($this->getTagLoader());
    }

    public function testInvoke()
    {
        self::assertInstanceOf(
            ExtractTag::class,
            $this->getStep()(
                $this->createMock(ManagerInterface::class),
                'foo',
            )
        );
    }
}
