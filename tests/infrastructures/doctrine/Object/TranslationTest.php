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
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Doctrine\Object\Translation;
use Teknoo\Tests\East\Website\Object\Traits\PopulateObjectTrait;

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
#[CoversClass(Translation::class)]
class TranslationTest extends TestCase
{
    use PopulateObjectTrait;

    public function buildObject(): Translation
    {
        return new Translation();
    }

    public function testGetIdentifier()
    {
        self::assertIsString($this->generateObjectPopulated(['id' => 'foo'])->getIdentifier());
    }

    public function testSetLocale()
    {
        self::assertInstanceOf(
            Translation::class,
            $this->buildObject()->setLocale('foo')
        );
    }

    public function testSetField()
    {
        self::assertInstanceOf(
            Translation::class,
            $this->buildObject()->setField('foo')
        );
    }

    public function testSetObjectClass()
    {
        self::assertInstanceOf(
            Translation::class,
            $this->buildObject()->setObjectClass('foo')
        );
    }

    public function testSetForeignKey()
    {
        self::assertInstanceOf(
            Translation::class,
            $this->buildObject()->setForeignKey('foo')
        );
    }

    public function testSetContent()
    {
        self::assertInstanceOf(
            Translation::class,
            $this->buildObject()->setContent('foo')
        );
    }
}
