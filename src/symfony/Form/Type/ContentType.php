<?php

declare(strict_types=1);

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2019 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\WebsiteBundle\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Object\User;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @SuppressWarnings(PHPMD)
 */
class ContentType extends AbstractType
{
    use TranslatableTrait;

    /**
     * To configure this form and fields to display.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     * @return self
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        $builder->add(
            'author',
            DocumentType::class,
            [
                'class' => User::class,
                'required' => true,
                'multiple' => false,
                'choice_label' => 'username',
                'query_builder' => function (DocumentRepository $repository) {
                    return $repository->createQueryBuilder()
                        ->field('deletedAt')->equals(null);
                }
            ]
        );
        $builder->add(
            'type',
            DocumentType::class,
            [
                'class' => Type::class,
                'required' => true,
                'multiple' => false,
                'choice_label' => 'name',
                'query_builder' => function (DocumentRepository $repository) {
                    return $repository->createQueryBuilder()
                        ->field('deletedAt')->equals(null);
                }
            ]
        );
        $builder->add('title', TextType::class, ['required'=>true]);
        $builder->add('subtitle', TextType::class, ['required'=>false]);
        $builder->add('slug', TextType::class, ['required'=>false]);
        $builder->add('description', TextareaType::class, ['required'=>false]);
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
            function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();

                if (!$data instanceof Content || !$data->getType() instanceof Type) {
                    return;
                }

                $data->isInState([Content\Published::class], function () use ($data, $form) {
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
                    switch ($block->getType()) {
                        case 'textarea':
                        case 'raw':
                            $formType = TextareaType::class;
                            break;
                        case 'numeric':
                            $formType = NumberType::class;
                            break;
                        case 'text':
                        case 'image':
                        default:
                            $formType = TextType::class;
                            break;
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
                            'attr' => ['data-type' => $block->getType()]
                        ]
                    );
                }
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $contentObject = $form->getNormData();

                if (!$contentObject instanceof Content || !$contentObject->getType() instanceof Type) {
                    return;
                }

                $type = $contentObject->getType();
                $contentValue = [];
                foreach ($type->getBlocks() as $block) {
                    if (isset($data[$block->getName()])) {
                        $contentValue[$block->getName()] = $data[$block->getName()];
                    }
                }

                $contentObject->setParts($contentValue);
            }
        );

        $this->addTranslatableLocaleFieldHidden($builder);

        return $this;
    }
}
