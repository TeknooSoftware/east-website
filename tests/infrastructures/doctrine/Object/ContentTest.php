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

namespace Teknoo\Tests\East\Website\Doctrine\Object;

use PHPUnit\Framework\Attributes\CoversClass;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Object\Content as ContentOriginal;
use Teknoo\East\Website\Object\Content\Draft;
use Teknoo\East\Website\Object\Content\Published;
use Teknoo\Tests\East\Website\Object\ContentTest as OriginalTest;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(Published::class)]
#[CoversClass(Draft::class)]
#[CoversClass(ContentOriginal::class)]
#[CoversClass(Content::class)]
class ContentTest extends OriginalTest
{
    #[\Override]
    public function buildObject(): Content
    {
        return new Content();
    }

    #[\Override]
    public function testGetLocaleField(): void
    {
        $this->assertEquals('fooBar', $this->generateObjectPopulated(['localeField' => 'fooBar'])->getLocaleField());
    }

    #[\Override]
    public function testSetLocaleField(): void
    {
        $Object = $this->buildObject();
        $this->assertInstanceOf($Object::class, $Object->setLocaleField('fooBar'));

        $this->assertEquals('fooBar', $Object->getLocaleField());
    }
}
