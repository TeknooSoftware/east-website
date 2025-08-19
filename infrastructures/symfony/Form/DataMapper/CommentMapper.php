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

namespace Teknoo\East\WebsiteBundle\Form\DataMapper;

use DateTimeInterface;
use Symfony\Component\Form\DataMapperInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Object\Comment;
use Traversable;

use function array_values;

/**
 * DataMapper dedicated to Comment. On Comment, original content are readonly.
 * Only Moderated properties can be updated.
 * This datamapper will fill all form's fields, but it will only fetch moderations fields and call the dedicated
 * Comment's method (moderate) to update its moderation values and its states. All other changes will be ignored
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class CommentMapper implements DataMapperInterface
{
    public function __construct(
        private readonly DatesService $datesService,
    ) {
    }

    public function mapDataToForms(mixed $viewData, Traversable $forms): void
    {
        if (!$viewData instanceof Comment) {
            return;
        }

        foreach ($forms as $form) {
            match ($form->getName()) {
                'author' => $form->setData($viewData->getAuthor()),
                'remoteIp' => $form->setData($viewData->getRemoteIp()),
                'title' => $form->setData($viewData->getTitle()),
                'content' => $form->setData($viewData->getContent()),
                'postAt' => $form->setData($viewData->getPostAt()),
                'moderatedAt' => $form->setData($viewData->getModeratedAt()),
                'moderatedAuthor' => $form->setData($viewData->getModeratedAuthor()),
                'moderatedTitle' => $form->setData($viewData->getModeratedTitle()),
                'moderatedContent' => $form->setData($viewData->getModeratedContent()),
                default => null,
            };
        }
    }

    public function mapFormsToData(Traversable $forms, mixed &$viewData): void
    {
        if (!$viewData instanceof Comment) {
            return;
        }

        $moderatesValues = [
            'moderatedAuthor' => '',
            'moderatedTitle' => '',
            'moderatedContent' => '',
        ];

        foreach ($forms as $form) {
            $name = $form->getName();

            if (!isset($moderatesValues[$name])) {
                continue;
            }

            $moderatesValues[$name] = match ($name) {
                'moderatedAuthor' => (string) $form->getData(),
                'moderatedTitle' => (string) $form->getData(),
                'moderatedContent' => (string) $form->getData(),
            };
        }

        $currentValues = [
            (string) $viewData->getModeratedAuthor(),
            (string) $viewData->getModeratedTitle(),
            (string) $viewData->getModeratedContent(),
        ];

        $moderatesValues = array_values($moderatesValues);
        if ($currentValues === $moderatesValues) {
            return;
        }

        $this->datesService->passMeTheDate(
            fn (DateTimeInterface $now): Comment => $viewData->moderate($now, ...$moderatesValues),
        );
    }
}
