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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Website\Object\Comment;
use Teknoo\East\WebsiteBundle\Form\DataMapper\CommentMapper;

/**
 * Form Type used to moderate a comment. Original field are disabled, only moderation fields can be updated.
 * This form can only be used with a CommentMapper instance to load and persist values
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ModerateCommentType extends AbstractType
{
    public function __construct(
        private readonly CommentMapper $commentMapper,
    ) {
    }

    /**
     * @param FormBuilderInterface<Comment> $builder
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        $builder->add('author', TextType::class, ['required' => true, 'disabled' => true]);
        $builder->add('remoteIp', TextType::class, ['required' => true, 'disabled' => true]);
        $builder->add('title', TextType::class, ['required' => true, 'disabled' => true]);
        $builder->add('content', TextareaType::class, ['required' => true, 'disabled' => true]);
        $builder->add('postAt', DateTimeType::class, ['required' => true, 'disabled' => true]);
        $builder->add('moderatedAt', DateTimeType::class, ['required' => false]);
        $builder->add('moderatedAuthor', TextType::class, ['required' => false]);
        $builder->add('moderatedTitle', TextType::class, ['required' => false]);
        $builder->add('moderatedContent', TextareaType::class, ['required' => false]);

        $builder->setDataMapper($this->commentMapper);

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);

        return $this;
    }
}
