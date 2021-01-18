<?php

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\Website\Recipe\Step;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Website\Recipe\Step\ExtractPage;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers \Teknoo\East\Website\Recipe\Step\ExtractPage
 */
class ExtractPageTest extends TestCase
{
    public function buildStep(): ExtractPage
    {
        return new ExtractPage();
    }

    public function testInvokeBadManager()
    {
        $this->expectException(\TypeError::class);

        $this->buildStep()(
            new \stdClass(),
            '1'
        );
    }

    public function testInvokeBadPage()
    {
        $this->expectException(\TypeError::class);

        $this->buildStep()(
            $this->createMock(ManagerInterface::class),
            new \stdClass()
        );
    }

    public function testInvokeWithoutPage()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::once())->method('updateWorkPlan')->with([
            'page' => 1
        ]);
        $manager->expects(self::never())->method('error');

        self::assertInstanceOf(
            ExtractPage::class,
            $this->buildStep()(
                $manager
            )
        );
    }

    public function testInvokeWithPage()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::once())->method('updateWorkPlan')->with([
            'page' => 2
        ]);
        $manager->expects(self::never())->method('error');

        self::assertInstanceOf(
            ExtractPage::class,
            $this->buildStep()(
                $manager,
                '2'
            )
        );
    }

    public function testInvokeWithInvalidPage()
    {
        $manager = $this->createMock(ManagerInterface::class);
        $manager->expects(self::once())->method('updateWorkPlan')->with([
            'page' => 1
        ]);
        $manager->expects(self::never())->method('error');

        self::assertInstanceOf(
            ExtractPage::class,
            $this->buildStep()(
                $manager,
                'foo'
            )
        );
    }
}
