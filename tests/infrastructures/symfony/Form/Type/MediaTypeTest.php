<?php

/**
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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\Tests\East\WebsiteBundle\Form\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Teknoo\East\Website\Object\Media;
use Teknoo\East\WebsiteBundle\Form\Type\MediaType;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 * @covers      \Teknoo\East\WebsiteBundle\Form\Type\MediaType
 */
class MediaTypeTest extends TestCase
{
    public function buildForm()
    {
        return new MediaType();
    }

    public function testBuildForm()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) {
                self::assertEquals(FormEvents::PRE_SUBMIT, $name);

                $form = $this->createMock(FormInterface::class);
                $event = new FormEvent($form, []);
                $callable($event);
            });

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, [])
        );
    }

    public function testBuildFormPopulatedFileError()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) {
                self::assertEquals(FormEvents::PRE_SUBMIT, $name);

                $form = $this->createMock(FormInterface::class);
                $form->expects(self::once())->method('getNormData')
                    ->willReturn($this->createMock(Media::class));
                $image = $this->createMock(UploadedFile::class);
                $image->expects(self::any())->method('getError')->willReturn(1);
                $image->expects(self::never())->method('getSize')->willReturn(123);
                $form->expects(self::once())->method('addError');

                $event = new FormEvent($form, ['image' => $image]);
                $callable($event);
            });

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, [])
        );
    }

    public function testBuildFormPopulatedFile()
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addEventListener')
            ->willReturnCallback(function ($name, $callable) {
                self::assertEquals(FormEvents::PRE_SUBMIT, $name);

                $form = $this->createMock(FormInterface::class);
                $form->expects(self::once())->method('getNormData')
                    ->willReturn($this->createMock(Media::class));
                $image = $this->createMock(UploadedFile::class);
                $image->expects(self::any())->method('getError')->willReturn(0);
                $image->expects(self::any())->method('getSize')->willReturn(123);
                $form->expects(self::never())->method('addError');
                $event = new FormEvent($form, ['image' => $image]);
                $callable($event);
            });

        self::assertInstanceOf(
            AbstractType::class,
            $this->buildForm()->buildForm($builder, [])
        );
    }
}
