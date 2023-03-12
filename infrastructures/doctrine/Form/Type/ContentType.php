<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Doctrine\Form\Type;

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
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Object\BlockType;
use Teknoo\East\Website\Object\Content\Published;
use Teknoo\East\Website\Object\Type;

/**
 * Symfony Form dedicated to manage translatable Content Object in a Symfony Website.
 * This form is placed in this namespace to use the good Symfony Form Doctrine Type to link a content to an author and
 * to a type. Author list and Type list are populated from theirs respective repository.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/states Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class ContentType extends AbstractType
{
    use TranslatableTrait;

    public function __construct(
        private readonly ?HtmlSanitizerInterface $sanitizer = null,
        private readonly ?string $sanitizeContext = null,
        private readonly ?string $contentSanitzedSalt = null,
    ) {
    }

    private static function preSetDataallback(FormEvent $event): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (!$data instanceof Content || !$data->getType() instanceof Type) {
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
                    'data' => $data->getPublishedAt()
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
                $block->getName(),
                $formType,
                [
                    'mapped' => false,
                    'data' => $value,
                    'required' => false,
                    'attr' => ['data-type' => $block->getType()->value]
                ]
            );
        }
    }

    private function preSubmitCallback(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var array<string, string> $data */
        $data = $event->getData();
        $contentObject = $form->getNormData();

        if (!$contentObject instanceof Content || !$contentObject->getType() instanceof Type) {
            return;
        }

        $type = $contentObject->getType();
        $contentValues = [];
        $sanitzedContentValues = [];
        foreach ($type->getBlocks() as $block) {
            if (!isset($data[$block->getName()])) {
                continue;
            }

            $value = $data[$block->getName()];
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
                'query_builder' => static fn(ObjectRepository $repository) => $repository->createQueryBuilder()
                    ->field('deletedAt')->equals(null)
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
                'query_builder' => static fn(ObjectRepository $repository) => $repository->createQueryBuilder()
                    ->field('deletedAt')->equals(null)
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
            self::preSetDataallback(...),
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
            'doctrine_type' => '',
        ));

        return $this;
    }
}
