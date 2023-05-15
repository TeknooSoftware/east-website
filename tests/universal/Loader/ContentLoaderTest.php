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

namespace Teknoo\Tests\East\Website\Loader;

use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\DBSource\RepositoryInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Contracts\DBSource\Repository\ContentRepositoryInterface;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Object\Content;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 * @covers      \Teknoo\East\Website\Loader\ContentLoader
 */
class ContentLoaderTest extends TestCase
{
    use LoaderTestTrait;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RepositoryInterface
     */
    public function getRepositoryMock(): RepositoryInterface
    {
        if (!$this->repository instanceof RepositoryInterface) {
            $this->repository = $this->createMock(ContentRepositoryInterface::class);
        }

        return $this->repository;
    }

    /**
     * @return LoaderInterface|ContentLoader
     */
    public function buildLoader(): LoaderInterface
    {
        $repository = $this->getRepositoryMock();
        return new ContentLoader($repository);
    }

    /**
     * @return Content
     */
    public function getEntity()
    {
        return new Content();
    }
}
