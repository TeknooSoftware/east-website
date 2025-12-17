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

namespace Teknoo\Tests\East\Website\Recipe\Plan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Teknoo\East\Common\Contracts\Recipe\Step\ListObjectsAccessControlInterface;
use Teknoo\East\Common\Contracts\Recipe\Step\SearchFormLoaderInterface;
use Teknoo\East\Common\Recipe\Step\ExtractOrder;
use Teknoo\East\Common\Recipe\Step\LoadListObjects;
use Teknoo\East\Common\Recipe\Step\RenderList;
use Teknoo\East\Common\Recipe\Step\ExtractPage;
use Teknoo\East\Common\Recipe\Step\RenderError;
use Teknoo\East\Website\Recipe\Plan\ListCommentsOfPostEndPoint;
use Teknoo\East\Website\Recipe\Step\LoadPostFromRequest;
use Teknoo\East\Website\Recipe\Step\PrepareCriteriaFromPost;
use Teknoo\Recipe\EditablePlanInterface;
use Teknoo\Recipe\RecipeInterface;
use Teknoo\Tests\Recipe\Plan\EditablePlanTestTrait;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ListCommentsOfPostEndPoint::class)]
class ListCommentsOfPostEndPointTest extends TestCase
{
    use EditablePlanTestTrait;

    private (RecipeInterface&Stub)|(RecipeInterface&MockObject)|null $recipe = null;

    private (ExtractPage&Stub)|(ExtractPage&MockObject)|null $extractPage = null;

    private (ExtractOrder&Stub)|(ExtractOrder&MockObject)|null $extractOrder = null;

    private (LoadPostFromRequest&Stub)|(LoadPostFromRequest&MockObject)|null $loadPostFromRequest = null;

    private (PrepareCriteriaFromPost&Stub)|(PrepareCriteriaFromPost&MockObject)|null $prepareCriteriaFromPost = null;

    private (LoadListObjects&Stub)|(LoadListObjects&MockObject)|null $loadListObjects = null;

    private (RenderList&Stub)|(RenderList&MockObject)|null $renderList = null;

    private (RenderError&Stub)|(RenderError&MockObject)|null $renderError = null;

    private (SearchFormLoaderInterface&Stub)|(SearchFormLoaderInterface&MockObject)|null $searchFormLoader = null;

    private (ListObjectsAccessControlInterface&Stub)|(ListObjectsAccessControlInterface&MockObject)|null $listObjectsAccessControl = null;
    public function getRecipe(bool $stub = false): (RecipeInterface&Stub)|(RecipeInterface&MockObject)
    {
        if (!$this->recipe instanceof RecipeInterface) {
            if ($stub) {
                $this->recipe = $this->createStub(RecipeInterface::class);
            } else {
                $this->recipe = $this->createMock(RecipeInterface::class);
            }
        }

        return $this->recipe;
    }

    public function getExtractPage(bool $stub = false): (ExtractPage&Stub)|(ExtractPage&MockObject)
    {
        if (!$this->extractPage instanceof ExtractPage) {
            if ($stub) {
                $this->extractPage = $this->createStub(ExtractPage::class);
            } else {
                $this->extractPage = $this->createMock(ExtractPage::class);
            }
        }

        return $this->extractPage;
    }

    public function getExtractOrder(bool $stub = false): (ExtractOrder&Stub)|(ExtractOrder&MockObject)
    {
        if (!$this->extractOrder instanceof ExtractOrder) {
            if ($stub) {
                $this->extractOrder = $this->createStub(ExtractOrder::class);
            } else {
                $this->extractOrder = $this->createMock(ExtractOrder::class);
            }
        }

        return $this->extractOrder;
    }

    public function getLoadPostFromRequest(bool $stub = false): (LoadPostFromRequest&Stub)|(LoadPostFromRequest&MockObject)
    {
        if (!$this->loadPostFromRequest instanceof LoadPostFromRequest) {
            if ($stub) {
                $this->loadPostFromRequest = $this->createStub(LoadPostFromRequest::class);
            } else {
                $this->loadPostFromRequest = $this->createMock(LoadPostFromRequest::class);
            }
        }

        return $this->loadPostFromRequest;
    }

    public function getPrepareCriteriaFromPost(bool $stub = false): (PrepareCriteriaFromPost&Stub)|(PrepareCriteriaFromPost&MockObject)
    {
        if (!$this->prepareCriteriaFromPost instanceof PrepareCriteriaFromPost) {
            if ($stub) {
                $this->prepareCriteriaFromPost = $this->createStub(PrepareCriteriaFromPost::class);
            } else {
                $this->prepareCriteriaFromPost = $this->createMock(PrepareCriteriaFromPost::class);
            }
        }

        return $this->prepareCriteriaFromPost;
    }

    public function getLoadListObjects(bool $stub = false): (LoadListObjects&Stub)|(LoadListObjects&MockObject)
    {
        if (!$this->loadListObjects instanceof LoadListObjects) {
            if ($stub) {
                $this->loadListObjects = $this->createStub(LoadListObjects::class);
            } else {
                $this->loadListObjects = $this->createMock(LoadListObjects::class);
            }
        }

        return $this->loadListObjects;
    }

    public function getRenderList(bool $stub = false): (RenderList&Stub)|(RenderList&MockObject)
    {
        if (!$this->renderList instanceof RenderList) {
            if ($stub) {
                $this->renderList = $this->createStub(RenderList::class);
            } else {
                $this->renderList = $this->createMock(RenderList::class);
            }
        }

        return $this->renderList;
    }

    public function getRenderError(bool $stub = false): (RenderError&Stub)|(RenderError&MockObject)
    {
        if (!$this->renderError instanceof RenderError) {
            if ($stub) {
                $this->renderError = $this->createStub(RenderError::class);
            } else {
                $this->renderError = $this->createMock(RenderError::class);
            }
        }

        return $this->renderError;
    }

    public function getSearchFormLoader(bool $stub = false): (SearchFormLoaderInterface&Stub)|(SearchFormLoaderInterface&MockObject)
    {
        if (!$this->searchFormLoader instanceof SearchFormLoaderInterface) {
            if ($stub) {
                $this->searchFormLoader = $this->createStub(SearchFormLoaderInterface::class);
            } else {
                $this->searchFormLoader = $this->createMock(SearchFormLoaderInterface::class);
            }
        }

        return $this->searchFormLoader;
    }

    public function getListObjectsAccessControl(bool $stub = false): (ListObjectsAccessControlInterface&Stub)|(ListObjectsAccessControlInterface&MockObject)
    {
        if (!$this->listObjectsAccessControl instanceof ListObjectsAccessControlInterface) {
            if ($stub) {
                $this->listObjectsAccessControl = $this->createStub(ListObjectsAccessControlInterface::class);
            } else {
                $this->listObjectsAccessControl = $this->createMock(ListObjectsAccessControlInterface::class);
            }
        }

        return $this->listObjectsAccessControl;
    }

    public function buildPlan(): EditablePlanInterface
    {
        return new ListCommentsOfPostEndPoint(
            $this->getRecipe(true),
            $this->getExtractPage(true),
            $this->getExtractOrder(true),
            $this->getLoadPostFromRequest(true),
            $this->getPrepareCriteriaFromPost(true),
            $this->getLoadListObjects(true),
            $this->getRenderList(true),
            $this->getRenderError(true),
            $this->getSearchFormLoader(true),
            $this->getListObjectsAccessControl(true),
            'foo',
        );
    }
}
