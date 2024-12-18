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
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Translation\Doctrine\Form\Type\TranslatableTrait;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Doctrine\Object\Item;

/**
 * Symfony Form dedicated to manage translatable Item Object in a Symfony Website.
 * This form is placed in this namespace to use the good Symfony Form Doctrine Type to link an item to a parent and
 * to a content. Parents list and Contents list are populated from theirs respective repository.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class ItemType extends AbstractType
{
    use TranslatableTrait;

    /**
     * @param FormBuilderInterface<Item> $builder
     * @param array<string, string> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): self
    {
        $builder->add('name', TextType::class, ['required' => true]);
        $builder->add('location', TextType::class, ['required' => true]);
        $builder->add(
            'parent',
            $options['doctrine_type'],
            [
                'class' => Item::class,
                'required' => false,
                'multiple' => false,
                'choice_label' => 'name',
                'query_builder' => static fn(ObjectRepository $repository): Builder => $repository
                    ->createQueryBuilder()
                        ->field('deletedAt')->equals(null)
                    ->sort('name', 'asc')
            ]
        );
        $builder->add(
            'content',
            $options['doctrine_type'],
            [
                'class' => Content::class,
                'required' => false,
                'multiple' => false,
                'choice_label' => 'title',
                'query_builder' => static fn(ObjectRepository $repository): Builder => $repository
                    ->createQueryBuilder()
                        ->field('deletedAt')->equals(null)
                    ->sort('title', 'asc')
            ]
        );
        $builder->add('slug', TextType::class, ['required' => false]);
        $builder->add('slug', TextType::class, ['required' => false]);
        $builder->add('hidden', CheckboxType::class, ['required' => false]);
        $builder->add('position', IntegerType::class, ['required' => false]);

        $this->addTranslatableLocaleFieldHidden($builder);

        return $this;
    }

    public function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(array(
            'data_class' => Item::class,
        ));

        $resolver->setRequired(['doctrine_type']);
        $resolver->setAllowedTypes('doctrine_type', 'string');

        return $this;
    }
}
