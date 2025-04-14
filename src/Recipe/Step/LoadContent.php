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

namespace Teknoo\East\Website\Recipe\Step;

use DateTimeInterface;
use DomainException;
use RuntimeException;
use SensitiveParameter;
use Teknoo\East\Common\View\ParametersBag;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\Recipe\Promise\Promise;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Query\Content\PublishedContentFromSlugQuery;
use Throwable;

/**
 * Step recipe to load a published Content instance (published before the current date), from its slug, thank to
 * the Content's loader and put it into the workplan at Content::class key, and `objectInstance`. The template file
 * to use with the fetched content is also injected to the template.
 * The content is also inject to view's variables through of the Bag, under the key `content`.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class LoadContent
{
    public function __construct(
        private readonly ContentLoader $contentLoader,
        private readonly DatesService $datesService,
    ) {
    }

    public function __invoke(
        string $slug,
        ManagerInterface $manager,
        ParametersBag $bag,
    ): self {
        $error = static function (#[SensitiveParameter] Throwable $error) use ($manager): void {
            if ($error instanceof DomainException) {
                $error = new DomainException($error->getMessage(), 404, $error);
            }

            $manager->error($error);
        };

        /** @var Promise<Content, mixed, mixed> $fetchPromise */
        $fetchPromise = new Promise(
            static function (Content $content) use ($manager, $error, $bag): void {
                $type = $content->getType();
                if (null === $type) {
                    $error(new RuntimeException('Content type is not available'));

                    return;
                }

                $manager->updateWorkPlan([
                    Content::class => $content,
                    'objectInstance' => $content,
                    'template' => $type->getTemplate(),
                ]);

                $bag->set('content', $content);
            },
            $error
        );

        $this->datesService->passMeTheDate(
            fn (DateTimeInterface $date) => $this->contentLoader->fetch(
                new PublishedContentFromSlugQuery($slug, $date),
                $fetchPromise
            ),
        );

        return $this;
    }
}
