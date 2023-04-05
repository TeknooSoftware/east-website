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

namespace Teknoo\Tests\East\Website\Doctrine\Recipe\Step;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Website\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Website\Doctrine\Recipe\Step\LoadTranslations;

/**
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @covers \Teknoo\East\Website\Doctrine\Recipe\Step\LoadTranslations
 */
class LoadTranslationsTest extends TestCase
{
    public function testInvoke()
    {
        self::assertInstanceOf(
            LoadTranslations::class,
            (new LoadTranslations(null))()
        );

        self::assertInstanceOf(
            LoadTranslations::class,
            (new LoadTranslations($this->createMock(TranslationManagerInterface::class)))()
        );
    }
}
