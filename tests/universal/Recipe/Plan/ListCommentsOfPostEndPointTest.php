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
 * @link        https://teknoo.software/east-collection/website Project website
 *
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Recipe\Plan;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ListCommentsOfPostEndPoint::class)]
class ListCommentsOfPostEndPointTest extends TestCase
{
    use EditablePlanTestTrait;

    private (RecipeInterface&MockObject)|null $recipe = null;

    private (ExtractPage&MockObject)|null $extractPage = null;

    private (ExtractOrder&MockObject)|null $extractOrder = null;

    private (LoadPostFromRequest&MockObject)|null $loadPostFromRequest = null;

    private (PrepareCriteriaFromPost&MockObject)|null $prepareCriteriaFromPost = null;

    private (LoadListObjects&MockObject)|null $loadListObjects = null;

    private (RenderList&MockObject)|null $renderList = null;

    private (RenderError&MockObject)|null $renderError = null;

    private (SearchFormLoaderInterface&MockObject)|null $searchFormLoader = null;

    private (ListObjectsAccessControlInterface&MockObject)|null $listObjectsAccessControl = null;

    public function getRecipe(): RecipeInterface&MockObject
    {
        if (null === $this->recipe) {
            $this->recipe = $this->createMock(RecipeInterface::class);
        }

        return $this->recipe;
    }

    public function getExtractPage(): ExtractPage&MockObject
    {
        if (null === $this->extractPage) {
            $this->extractPage = $this->createMock(ExtractPage::class);
        }

        return $this->extractPage;
    }

    public function getExtractOrder(): ExtractOrder&MockObject
    {
        if (null === $this->extractOrder) {
            $this->extractOrder = $this->createMock(ExtractOrder::class);
        }

        return $this->extractOrder;
    }

    public function getLoadPostFromRequest(): LoadPostFromRequest&MockObject
    {
        if (null === $this->loadPostFromRequest) {
            $this->loadPostFromRequest = $this->createMock(LoadPostFromRequest::class);
        }

        return $this->loadPostFromRequest;
    }

    public function getPrepareCriteriaFromPost(): PrepareCriteriaFromPost&MockObject
    {
        if (null === $this->prepareCriteriaFromPost) {
            $this->prepareCriteriaFromPost = $this->createMock(PrepareCriteriaFromPost::class);
        }

        return $this->prepareCriteriaFromPost;
    }

    public function getLoadListObjects(): LoadListObjects&MockObject
    {
        if (null === $this->loadListObjects) {
            $this->loadListObjects = $this->createMock(LoadListObjects::class);
        }

        return $this->loadListObjects;
    }

    public function getRenderList(): RenderList&MockObject
    {
        if (null === $this->renderList) {
            $this->renderList = $this->createMock(RenderList::class);
        }

        return $this->renderList;
    }

    public function getRenderError(): RenderError&MockObject
    {
        if (null === $this->renderError) {
            $this->renderError = $this->createMock(RenderError::class);
        }

        return $this->renderError;
    }

    public function getSearchFormLoader(): SearchFormLoaderInterface&MockObject
    {
        if (null === $this->searchFormLoader) {
            $this->searchFormLoader = $this->createMock(SearchFormLoaderInterface::class);
        }

        return $this->searchFormLoader;
    }

    public function getListObjectsAccessControl(): ListObjectsAccessControlInterface&MockObject
    {
        if (null === $this->listObjectsAccessControl) {
            $this->listObjectsAccessControl = $this->createMock(ListObjectsAccessControlInterface::class);
        }

        return $this->listObjectsAccessControl;
    }

    public function buildPlan(): EditablePlanInterface
    {
        return new ListCommentsOfPostEndPoint(
            $this->getRecipe(),
            $this->getExtractPage(),
            $this->getExtractOrder(),
            $this->getLoadPostFromRequest(),
            $this->getPrepareCriteriaFromPost(),
            $this->getLoadListObjects(),
            $this->getRenderList(),
            $this->getRenderError(),
            $this->getSearchFormLoader(),
            $this->getListObjectsAccessControl(),
            'foo',
        );
    }
}
