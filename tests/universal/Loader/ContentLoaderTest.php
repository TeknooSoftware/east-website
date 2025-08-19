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

namespace Teknoo\Tests\East\Website\Loader;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Object\Content;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ContentLoader::class)]
class ContentLoaderTest extends TestCase
{
    use LoaderTestTrait;

    private (RepositoryInterface&MockObject)|null $repository = null;

    public function getRepositoryMock(): RepositoryInterface&MockObject
    {
        if (!$this->repository instanceof RepositoryInterface) {
            $this->repository = $this->createMock(ContentRepositoryInterface::class);
        }

        return $this->repository;
    }

    public function buildLoader(): LoaderInterface
    {
        $repository = $this->getRepositoryMock();
        return new ContentLoader($repository);
    }

    public function getEntity(): Content
    {
        return new Content();
    }
}
