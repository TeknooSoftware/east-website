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

namespace Teknoo\East\Website\Doctrine\Form\Type;

use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Common\Object\User;
use Teknoo\East\Translation\Doctrine\Form\Type\TranslatableTrait;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Object\Content as OriginalContent;
use Teknoo\East\Website\Object\BlockType;
use Teknoo\East\Website\Object\Content\Published;
use Teknoo\East\Website\Object\Tag;
use Teknoo\East\Website\Object\Type;

use function str_replace;

/**
 * Symfony Form dedicated to manage translatable Content Object in a Symfony Website.
 * This form is placed in this namespace to use the good Symfony Form Doctrine Type to link a content to an author and
 * to a type. Author list and Type list are populated from theirs respective repository.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ContentType extends AbstractType
{
    use TranslatableTrait;

    public const BLOCK_PREFIX = 'block_';

    public function __construct(
        private readonly ?HtmlSanitizerInterface $sanitizer = null,
        private readonly ?string $sanitizeContext = null,
        private readonly ?string $contentSanitzedSalt = null,
    ) {
    }

    private static function preSetDataFallback(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (!$data instanceof OriginalContent || !$data->getType() instanceof Type) {
            return;
        }

        $data->isInState([Published::class], static function () use ($data, $form): void {
            $form->add(
                'publishedAt',
                DateTimeType::class,
                [
                    'required' => false,
                    'attr' => ['readonly' => true],
                    'widget' => 'single_text',
                    'mapped' => false,
                    'data' => $data->getPublishedAt(),
                ]
            );
        });

        $type = $data->getType();
        $parts = $data->getParts();

        foreach ($type->getBlocks() as $block) {
            $formType = match ($block->getType()) {
                BlockType::Textarea => TextareaType::class,
                BlockType::Raw => TextareaType::class,
                BlockType::Numeric => NumberType::class,
                BlockType::Text => TextType::class,
                BlockType::Image => TextType::class,
            };

            $value = '';
            if (isset($parts[$block->getName()])) {
                $value = $parts[$block->getName()];
            }

            $form->add(
                self::BLOCK_PREFIX . $block->getName(),
                $formType,
                [
                    'mapped' => false,
                    'data' => $value,
                    'required' => false,
                    'attr' => [
                        'data-type' => $block->getType()->value,
                    ],
                    'label' => str_replace('_', ' ', $block->getName()),
                ],
            );
        }
    }

    private function preSubmitCallback(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var array<string, string> $data */
        $data = $event->getData();
        $contentObject = $form->getNormData();

        if (!$contentObject instanceof OriginalContent || !$contentObject->getType() instanceof Type) {
            return;
        }

        $type = $contentObject->getType();
        $contentValues = [];
        $sanitzedContentValues = [];
        foreach ($type->getBlocks() as $block) {
            if (!isset($data[self::BLOCK_PREFIX . $block->getName()])) {
                continue;
            }

            $value = $data[self::BLOCK_PREFIX . $block->getName()];
            $contentValues[$block->getName()] = $value;

            if (null === $this->sanitizer) {
                continue;
            }

            if (null !== $this->contentSanitzedSalt && null === $this->sanitizeContext) {
                $sanitzedContentValues[$block->getName()] = $this->sanitizer->sanitize($value);
            } elseif (null !== $this->contentSanitzedSalt && null !== $this->sanitizeContext) {
                $sanitzedContentValues[$block->getName()] = $this->sanitizer->sanitizeFor(
                    $this->sanitizeContext,
                    $value,
                );
            }
        }

        $contentObject->setParts($contentValues);

        if (null !== $this->contentSanitzedSalt && null !== $this->sanitizer) {
            $contentObject->setSanitizedParts($sanitzedContentValues, $this->contentSanitzedSalt);
        }
    }

    /**
     * @param FormBuilderInterface<Content> $builder
     * @param array<string, string> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        $builder->add(
            'author',
            $options['doctrine_type'],
            [
                'class' => User::class,
                'required' => true,
                'multiple' => false,
                'choice_label' => 'userIdentifier',
                'query_builder' => static fn(ObjectRepository $repository): Builder => $repository
                    ->createQueryBuilder()
                        ->field('deletedAt')->equals(null)
                        ->sort('firstName', 'asc')
                        ->sort('lastName', 'asc')
                        ->sort('email', 'asc')
            ]
        );

        $builder->add(
            'type',
            $options['doctrine_type'],
            [
                'class' => Type::class,
                'required' => true,
                'multiple' => false,
                'choice_label' => 'name',
                'query_builder' => static fn(ObjectRepository $repository): Builder => $repository
                    ->createQueryBuilder()
                        ->field('deletedAt')->equals(null)
                        ->sort('name', 'asc')
            ]
        );

        $builder->add(
            'tags',
            $options['doctrine_type'],
            [
                'class' => Tag::class,
                'required' => false,
                'multiple' => true,
                'choice_label' => 'name',
                'query_builder' => static fn(ObjectRepository $repository): Builder => $repository
                    ->createQueryBuilder()
                    ->field('deletedAt')->equals(null)
                    ->sort('name', 'asc')
            ]
        );
        $builder->add('title', TextType::class, ['required' => true]);
        $builder->add('subtitle', TextType::class, ['required' => false]);
        $builder->add('slug', TextType::class, ['required' => false]);
        $builder->add('description', TextareaType::class, ['required' => false]);
        $builder->add(
            'publishedAt',
            DateTimeType::class,
            [
                'required' => false,
                'attr' => ['readonly' => true],
                'widget' => 'single_text',
                'mapped' => false,
            ]
        );

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            self::preSetDataFallback(...),
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            $this->preSubmitCallback(...),
        );

        $this->addTranslatableLocaleFieldHidden($builder);

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => Content::class,
        ));

        $resolver->setRequired(['doctrine_type']);
        $resolver->setAllowedTypes('doctrine_type', 'string');

        return $this;
    }
}
