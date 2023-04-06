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
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Recipe\Step;

use DomainException;
use RuntimeException;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Query\Content\PublishedContentFromSlugQuery;
use Throwable;

/**
 * Step recipe to load a published Content instance, from its slug, thank to the Content's loader and put it into the
 * workplan at Content::class key, and `objectInstance`. The template file to use with the fetched content is also
 * injected to the template.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class LoadContent
{
    public function __construct(
        private readonly ContentLoader $contentLoader,
    ) {
    }

    public function __invoke(string $slug, ManagerInterface $manager): self
    {
        $error = static function (Throwable $error) use ($manager): void {
            if ($error instanceof DomainException) {
                $error = new DomainException($error->getMessage(), 404, $error);
            }

            $manager->error($error);
        };

        /** @var Promise<Content, mixed, mixed> $fetchPromise */
        $fetchPromise = new Promise(
            static function (Content $content) use ($manager, $error): void {
                $type = $content->getType();
                if (null === $type) {
                    $error(new RuntimeException('Content type is not available'));

                    return;
                }

                $manager->updateWorkPlan([
                    Content::class => $content,
                    'objectInstance' => $content,
                    'objectViewKey' => 'content',
                    'template' => $type->getTemplate(),
                ]);
            },
            $error
        );

        $this->contentLoader->fetch(
            new PublishedContentFromSlugQuery($slug),
            $fetchPromise
        );

        return $this;
    }
}
