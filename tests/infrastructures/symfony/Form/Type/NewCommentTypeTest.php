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

namespace Teknoo\Tests\East\WebsiteBundle\Form\Type;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Time\DatesService;
use Teknoo\East\Website\Doctrine\Object\Comment;
use Teknoo\East\WebsiteBundle\Form\Type\NewCommentType;
use TypeError;

/**
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(NewCommentType::class)]
class NewCommentTypeTest extends TestCase
{
    use FormTestTrait;

    private (DatesService&MockObject)|null $datesService = null;

    private function getDatesService(): DatesService&MockObject
    {
        if (!$this->datesService) {
            $this->datesService = $this->createMock(DatesService::class);
        }

        return $this->datesService;
    }

    public function buildForm()
    {
        return new NewCommentType(
            $this->getDatesService(),
            $this->createMock(RequestStack::class),
            $this->createMock(ManagerInterface::class),
        );
    }
    protected function getOptions(): array
    {
        return [
            'comment_class' => Comment::class,
            'manager' => $this->createMock(ManagerInterface::class),
        ];
    }

    public function testConfigureOptions()
    {
        self::assertInstanceOf(
            NewCommentType::class,
            $this->buildForm()->configureOptions(
                $this->createMock(OptionsResolver::class)
            )
        );
    }

    public function testBuildFormWithoutCommentClass()
    {
        $this->expectException(TypeError::class);
        $this->buildForm()->buildForm(
            $this->createMock(FormBuilder::class),
            [],
        );
    }

    public function testBuildFormWithWrongCommentClass()
    {
        $this->expectException(TypeError::class);
        $this->buildForm()->buildForm(
            $this->createMock(FormBuilder::class),
            ['comment_class' => stdClass::class],
        );
    }

    public function testBuildFormWithWrongManager()
    {
        $this->expectException(TypeError::class);
        $this->buildForm()->buildForm(
            $this->createMock(FormBuilder::class),
            ['comment_class' => Comment::class],
        );
    }

    public function testBuildFormOnSubmitWithWrongDTOInstance()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->any())
            ->method('add')
            ->willReturnSelf();

        $builder->expects($this->any())
            ->method('addEventListener')
            ->willReturnCallback(
                function ($event, $callback) use ($builder) {
                    self::assertEquals(FormEvents::POST_SUBMIT, $event);
                    $callback(new FormEvent($this->createMock(FormInterface::class), new stdClass()));

                    return $builder;
                }
            );

        $this->getDatesService()->expects($this->never())->method('passMeTheDate');

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, $this->getOptions())
        );
    }

    public function testBuildFormOnSubmitWithGoodDTOInstanceAndFormValid()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->any())
            ->method('add')
            ->willReturnSelf();

        $builder->expects($this->any())
            ->method('addEventListener')
            ->willReturnCallback(
                function ($event, $callback) use ($builder) {
                    self::assertEquals(FormEvents::POST_SUBMIT, $event);
                    $dto = $this->createMock(\Teknoo\East\WebsiteBundle\Form\DTO\Comment::class);
                    $dto->expects($this->once())->method('persistInto');

                    $form = $this->createMock(FormInterface::class);
                    $form->expects($this->once())->method('isValid')->willReturn(true);
                    $callback(new FormEvent($form, $dto));

                    return $builder;
                }
            );

        $this->getDatesService()
            ->expects($this->once())
            ->method('passMeTheDate')->willReturnCallback(
                function ($callable) {
                    $callable(new \DateTimeImmutable('2025-01-01'));

                    return $this->getDatesService();
                }
        );

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, $this->getOptions())
        );
    }

    public function testBuildFormOnSubmitWithGoodDTOInstanceAndFormNotValid()
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->expects($this->any())
            ->method('add')
            ->willReturnSelf();

        $builder->expects($this->any())
            ->method('addEventListener')
            ->willReturnCallback(
                function ($event, $callback) use ($builder) {
                    self::assertEquals(FormEvents::POST_SUBMIT, $event);
                    $dto = $this->createMock(\Teknoo\East\WebsiteBundle\Form\DTO\Comment::class);
                    $dto->expects($this->never())->method('persistInto');

                    $form = $this->createMock(FormInterface::class);
                    $form->expects($this->once())->method('isValid')->willReturn(false);
                    $callback(new FormEvent($form, $dto));

                    return $builder;
                }
            );

        $this->getDatesService()
            ->expects($this->never())
            ->method('passMeTheDate');

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, $this->getOptions())
        );
    }
}
