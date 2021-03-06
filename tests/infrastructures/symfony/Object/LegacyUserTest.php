<?php

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
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

namespace Teknoo\Tests\East\WebsiteBundle\Object;

use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Teknoo\East\WebsiteBundle\Object\LegacyUser;
use Teknoo\East\Website\Object\User as BaseUser;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\WebsiteBundle\Object\LegacyUser
 */
class LegacyUserTest extends UserTest
{
    /**
     * @var BaseUser
     */
    private $user;

    /**
     * @return BaseUser|\PHPUnit\Framework\MockObject\MockObject
     */
    public function getUser(): BaseUser
    {
        if (!$this->user instanceof BaseUser) {
            $this->user = $this->createMock(BaseUser::class);
        }

        return $this->user;
    }

    public function buildObject(): LegacyUser
    {
        if (!interface_exists(LegacyPasswordAuthenticatedUserInterface::class)) {
            self::markTestSkipped();
        }

        return new LegacyUser($this->getUser());
    }

    public function testExceptionWithBadUser()
    {
        if (!interface_exists(LegacyPasswordAuthenticatedUserInterface::class)) {
            self::markTestSkipped();
        }

        $this->expectException(\TypeError::class);
        new LegacyUser(new \stdClass());
    }
}
