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
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\WebsiteBundle\EndPoint;

use Symfony\Bundle\TwigBundle\TwigEngine;
use Teknoo\East\Foundation\EndPoint\EndPointInterface;
use Teknoo\East\WebsiteBundle\EndPoint\StaticEndPoint;
use Teknoo\Tests\East\Website\EndPoint\StaticEndPointTraitTest;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\WebsiteBundle\EndPoint\StaticEndPoint
 */
class StaticEndPointTest extends StaticEndPointTraitTest
{
    /**
     * @var TwigEngine
     */
    protected $templating;

    /**
     * @return TwigEngine|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getTemplating(): TwigEngine
    {
        if (!$this->templating instanceof TwigEngine) {
            $this->templating = $this->createMock(TwigEngine::class);

            $this->templating->expects(self::any())
                ->method('render')
                ->willReturn('fooBar:executed');
        }

        return $this->templating;
    }

    public function buildEndPoint(): EndPointInterface
    {
        return (new StaticEndPoint())
            ->setTemplating($this->getTemplating());
    }
}