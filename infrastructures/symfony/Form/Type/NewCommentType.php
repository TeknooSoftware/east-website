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
use Symfony\Component\Validator\Constraints\NotBlank;
use Teknoo\East\CommonBundle\Contracts\Form\FormManagerAwareInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Object\Comment as CommentObject;
use Teknoo\East\WebsiteBundle\Form\DTO\Comment;
use TypeError;

use function implode;
use function is_a;
use function is_string;

/**
 * Form type to allow user to add a comment to a post. This form use the DTO Comment and not a Persisted Comment object
 * The DTO is able to convert to a persisted comment and persist it into the dedicated writer on the post submit event
 * of this form
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class NewCommentType extends AbstractType implements FormManagerAwareInterface
{
    public function __construct(
        private readonly DatesService $datesService,
        private readonly RequestStack $requestStack,
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

        if (
            !isset($options['manager'])
            || !$options['manager'] instanceof ManagerInterface
        ) {
            throw new TypeError('The option "manager" must be an instance of ' . ManagerInterface::class);
        }

        $builder->add(
            'author',
            TextType::class,
            [
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ]
            ]
        );

        $builder->add(
            'title',
            TextType::class,
            [
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ]
            ]
        );

        $builder->add(
            'content',
            TextareaType::class,
            [
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ]
            ]
        );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($options): void {
                $dto = $event->getData();

                if (!$dto instanceof Comment || !$event->getForm()->isValid()) {
                    return;
                }

                $this->datesService->passMeTheDate(
                    function (DateTimeInterface $date) use ($dto, $options): void {
                        $dto->persistInto(
                            $options['manager'],
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

        $resolver->setRequired(['comment_class', 'manager']);
        $resolver->setAllowedTypes('comment_class', 'string');
        $resolver->setAllowedTypes('manager', ManagerInterface::class);

        return $this;
    }
}
