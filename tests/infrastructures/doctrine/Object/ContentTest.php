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
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Doctrine\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Object\Content as ContentOriginal;
use Teknoo\East\Website\Object\Content\Draft;
use Teknoo\East\Website\Object\Content\Published;
use Teknoo\East\Website\Object\PublishableTrait;
use Teknoo\Tests\East\Website\Object\ContentTest as OriginalTest;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(Published::class)]
#[CoversClass(Draft::class)]
#[CoversClass(ContentOriginal::class)]
#[CoversTrait(PublishableTrait::class)]
#[CoversClass(Content::class)]
class ContentTest extends OriginalTest
{
    public function buildObject(): Content
    {
        return new Content();
    }

    public function testGetLocaleField()
    {
        self::assertEquals(
            'fooBar',
            $this->generateObjectPopulated(['localeField' => 'fooBar'])->getLocaleField()
        );
    }

    public function testSetLocaleField()
    {
        $Object = $this->buildObject();
        self::assertInstanceOf(
            $Object::class,
            $Object->setLocaleField('fooBar')
        );

        self::assertEquals(
            'fooBar',
            $Object->getLocaleField()
        );
    }
}
