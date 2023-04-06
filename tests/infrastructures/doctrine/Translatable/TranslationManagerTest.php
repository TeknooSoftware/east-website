<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
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

namespace Teknoo\Tests\East\Website\Doctrine\Translatable;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Doctrine\Translatable\Persistence\Adapter\ODM as ODMPersistence;
use Teknoo\East\Website\Doctrine\Translatable\TranslationManager;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @covers \Teknoo\East\Website\Doctrine\Translatable\TranslationManager
 */
class TranslationManagerTest extends TestCase
{
    public function testDeferringTranslationsLoading()
    {
        $mock = $this->createMock(ODMPersistence::class);
        $mock->expects(self::once())->method('setDeferred')->with(true);

        self::assertInstanceOf(
            TranslationManager::class,
            (new TranslationManager($mock))->deferringTranslationsLoading()
        );
    }

    public function testStopDeferringTranslationsLoading()
    {
        $mock = $this->createMock(ODMPersistence::class);
        $mock->expects(self::once())->method('executeAllDeferredLoadings');
        $mock->expects(self::once())->method('setDeferred')->with(false);

        self::assertInstanceOf(
            TranslationManager::class,
            (new TranslationManager($mock))->stopDeferringTranslationsLoading()
        );
    }
}