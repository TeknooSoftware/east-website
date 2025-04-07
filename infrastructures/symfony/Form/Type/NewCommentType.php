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

namespace Teknoo\East\WebsiteBundle\Form\Type;

use DateTimeInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Object\Comment as CommentObject;
use Teknoo\East\Website\Writer\CommentWriter;
use Teknoo\East\WebsiteBundle\Form\DTO\Comment;
use TypeError;

use function implode;
use function is_a;
use function is_string;

/**
 * Symfony form to edit East Website Page's Type and theirs dynamics blocks
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class NewCommentType extends AbstractType
{
    public function __construct(
        private readonly CommentWriter $commentWriter,
        private readonly DatesService $datesService,
        private readonly RequestStack $requestStack,
        private readonly ManagerInterface $manager,
    ) {
    }

    /**
     * @param FormBuilderInterface<Comment> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        if (
            !isset($options['comment_class'])
            || !is_string($options['comment_class'])
            || !is_a($options['comment_class'], CommentObject::class, true)
        ) {
            throw new TypeError('The option "comment_class" must be a class-string<CommentObject>');
        }

        $builder->add('author', TextType::class, ['required' => true]);
        $builder->add('title', TextType::class, ['required' => true]);
        $builder->add('content', TextareaType::class, ['required' => true]);

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($options): void {
                $dto = $event->getData();

                if (!$dto instanceof Comment) {
                    return;
                }

                $this->datesService->passMeTheDate(
                    function (DateTimeInterface $date) use ($dto, $options): void {
                        $dto->persistInto(
                            $this->manager,
                            $this->commentWriter,
                            $options['comment_class'],
                            implode(',', $this->requestStack->getMainRequest()?->getClientIps() ?? []),
                            $date,
                        );
                    }
                );
            }
        );

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);

        $resolver->setRequired(['comment_class']);
        $resolver->setAllowedTypes('comment_class', 'string');


        return $this;
    }
}
