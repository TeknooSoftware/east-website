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

namespace Teknoo\Tests\East\Website\Doctrine\Form\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Website\Doctrine\Form\Type\ItemType;
use Teknoo\Tests\East\WebsiteBundle\Form\Type\FormTestTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Teknoo\East\Website\Object\Environment;
use Teknoo\East\Website\Object\Environments;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ItemType::class)]
class ItemTypeTest extends TestCase
{
    use FormTestTrait;

    public function buildForm(): ItemType
    {
        return new ItemType();
    }

    public function testConfigureOptions(): void
    {
        $this->buildForm()->configureOptions(
            $this->createStub(OptionsResolver::class)
        );
        $this->assertTrue(true);
    }
    public function testBuildFormAddsTheEnvironmentField(): void
    {
        $validation = Environment::define('validation', Environment::default());
        $environments = new Environments([$validation]);

        $fields = [];
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder
            ->method('add')
            ->willReturnCallback(
                function ($child, $type, array $options = []) use ($builder, &$fields) {
                    $fields[$child] = [$type, $options];

                    return $builder;
                }
            );

        (new ItemType($environments))->buildForm($builder, ['doctrine_type' => ChoiceType::class]);

        $this->assertArrayHasKey('environment', $fields);
        [$type, $options] = $fields['environment'];
        $this->assertSame(ChoiceType::class, $type);
        $this->assertTrue($options['required']);
        //Only names, the objects accept a name in `setEnvironment()`: no conversion in the form
        $this->assertSame(['default' => 'default', 'validation' => 'validation'], $options['choices']);
        $this->assertArrayNotHasKey('choice_label', $options);
        $this->assertArrayNotHasKey('choice_value', $options);

        Environment::reset();
    }

    public function testBuildFormWithoutEnvironmentsListsOnlyTheDefaultEnvironment(): void
    {
        $fields = [];
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder
            ->method('add')
            ->willReturnCallback(
                function ($child, $type, array $options = []) use ($builder, &$fields) {
                    $fields[$child] = [$type, $options];

                    return $builder;
                }
            );

        (new ItemType())->buildForm($builder, ['doctrine_type' => ChoiceType::class]);

        $this->assertSame(['default' => 'default'], $fields['environment'][1]['choices']);
    }
}
