<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Doctrine\Translatable\Mapping;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Doctrine\Translatable\Mapping\Configuration;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @covers \Teknoo\East\Website\Doctrine\Translatable\Mapping\Configuration
 */
class ConfigurationTest extends TestCase
{
    public function build(): Configuration
    {
        return new Configuration(
            'fooBar',
            [
                'foo' => 'bar',
            ],
            false
        );
    }

    public function testGetKey()
    {
        self::assertEquals(
            'fooBar',
            $this->build()->getKey(),
        );
    }

    public function testGet()
    {
        self::assertEquals(
            [
                'foo' => 'bar',
            ],
            $this->build()->get(),
        );
    }

    public function testIsHit()
    {
        self::assertEquals(
            false,
            $this->build()->isHit(),
        );
        self::assertEquals(
            true,
            (new Configuration(
                'fooBar',
                [
                    'foo' => 'bar',
                ],
                true
            ))->isHit(),
        );
    }

    public function testSet()
    {
        $configuration = $this->build();

        self::assertInstanceOf(
            Configuration::class,
            $configuration->set(
                [
                    'bar' => 'foo',
                ],
            ),
        );

        self::assertEquals(
            [
                'bar' => 'foo',
            ],
            $configuration->get(),
        );
    }

    public function testExpiresAt()
    {
        $configuration = $this->build();
        self::assertNull($configuration->getExpiry());

        self::assertInstanceOf(
            Configuration::class,
            $configuration->expiresAt(
                $date = new \DateTime('2022-06-27')
            ),
        );

        self::assertIsFloat(
            $configuration->getExpiry()
        );

        self::assertInstanceOf(
            Configuration::class,
            $configuration->expiresAt(null),
        );

        self::assertNull($configuration->getExpiry());
    }

    public function testExpiresAfter()
    {
        $configuration = $this->build();
        self::assertNull($configuration->getExpiry());

        self::assertInstanceOf(
            Configuration::class,
            $configuration->expiresAfter(
                $intervale = new \DateInterval('P1W2D')
            ),
        );

        self::assertIsFloat(
            $configuration->getExpiry()
        );

        self::assertGreaterThan(
            (float) (new \DateTime('now'))->format('U.u'),
            $configuration->getExpiry()
        );

        self::assertInstanceOf(
            Configuration::class,
            $configuration->expiresAfter(null),
        );

        self::assertNull($configuration->getExpiry());
        self::assertInstanceOf(
            Configuration::class,
            $configuration->expiresAfter(1234),
        );

        self::assertIsFloat(
            $configuration->getExpiry()
        );

        self::assertGreaterThan(
            (float) (new \DateTime('now'))->format('U.u'),
            $configuration->getExpiry()
        );
    }
}
