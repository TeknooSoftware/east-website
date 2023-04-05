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

namespace Teknoo\Tests\East\Website\Doctrine\DBSource\Common;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Website\Doctrine\DBSource\Common\ContentRepository;
use Teknoo\Tests\East\Common\Doctrine\DBSource\Common\RepositoryTestTrait;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers \Teknoo\East\Website\Doctrine\DBSource\Common\ContentRepository
 */
class ContentRepositoryTest extends TestCase
{
    use RepositoryTestTrait;

    /**
     * @inheritDoc
     */
    public function buildRepository(): RepositoryInterface
    {
        return new ContentRepository($this->getDoctrineObjectRepositoryMock());
    }
}
