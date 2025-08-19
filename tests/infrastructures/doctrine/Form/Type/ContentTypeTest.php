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

use DateTime;
use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Teknoo\East\Website\Object\Block;
use Teknoo\East\Website\Doctrine\Object\Content;
use Teknoo\East\Website\Object\BlockType;
use Teknoo\East\Website\Object\Type;
use Teknoo\East\Website\Doctrine\Form\Type\ContentType;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(ContentType::class)]
class ContentTypeTest extends TestCase
{
    public function buildForm(): ContentType
    {
        return new ContentType();
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->method('addEventListener')
            ->willReturnCallback(function (string $name, callable $callable) use ($builder): MockObject {
                $form = $this->createMock(FormInterface::class);
                $content = new Content();
                $type = new Type();
                $type->setBlocks([
                    new Block('foo', BlockType::Text),
                    new Block('foo2', BlockType::Textarea),
                    new Block('foo3', BlockType::Numeric),
                    new Block('foo3', BlockType::Image),
                    new Block('foo4', BlockType::Raw),
                ]);
                $content->setType($type);
                $content->setParts(['foo' => 'bar']);

                $event = new FormEvent($form, $content);
                $callable($event);

                return $builder;
            });

        $builder
            ->method('add')
            ->willReturnCallback(
                function (string|FormBuilderInterface $child, ?string $type, array $options = []) use ($builder): MockObject {
                    if (DocumentType::class == $type && isset($options['query_builder'])) {
                        $qBuilder = $this->createMock(Builder::class);
                        $qBuilder->expects($this->once())
                            ->method('field')
                            ->with('deletedAt')
                            ->willReturnSelf();

                        $qBuilder->expects($this->once())
                            ->method('equals')
                            ->with(null)
                            ->willReturnSelf();

                        $repository = $this->createMock(DocumentRepository::class);
                        $repository->expects($this->once())
                            ->method('createQueryBuilder')
                            ->willReturn($qBuilder);

                        $options['query_builder']($repository);
                    }

                    return $builder;
                }
            );

        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->buildForm($builder, ['doctrine_type' => ChoiceType::class]));
    }

    public function testBuildFormWithPublishedContent(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->method('addEventListener')
            ->willReturnCallback(function (string $name, callable $callable) use ($builder): MockObject {
                $form = $this->createMock(FormInterface::class);
                $content = new Content();
                $type = new Type();
                $type->setBlocks([
                    new Block('foo', BlockType::Text),
                    new Block('foo2', BlockType::Textarea),
                    new Block('foo3', BlockType::Image)
                ]);
                $content->setType($type);
                $content->setParts(['foo' => 'bar']);
                $content->setPublishedAt(new DateTime('2017-11-01'));

                $event = new FormEvent($form, $content);
                $callable($event);

                return $builder;
            });

        $builder
            ->method('add')
            ->willReturnCallback(
                function (string|FormBuilderInterface $child, ?string $type, array $options = []) use ($builder): MockObject {
                    if (DocumentType::class == $type && isset($options['query_builder'])) {
                        $qBuilder = $this->createMock(Builder::class);
                        $qBuilder->expects($this->once())
                            ->method('field')
                            ->with('deletedAt')
                            ->willReturnSelf();

                        $qBuilder->expects($this->once())
                            ->method('equals')
                            ->with(null)
                            ->willReturnSelf();

                        $repository = $this->createMock(DocumentRepository::class);
                        $repository->expects($this->once())
                            ->method('createQueryBuilder')
                            ->willReturn($qBuilder);

                        $options['query_builder']($repository);
                    }

                    return $builder;
                }
            );

        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->buildForm($builder, ['doctrine_type' => ChoiceType::class]));
    }

    public function testBuildFormSubmittedData(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder
            ->method('addEventListener')
            ->willReturnCallback(function (string $name, callable $callable) use ($builder): MockObject {
                $form = $this->createMock(FormInterface::class);
                $content = new Content();
                $type = new Type();
                $type->setBlocks([new Block('foo', BlockType::Text), new Block('foo2', BlockType::Text)]);
                $content->setType($type);
                $form->method('getNormData')->willReturn($content);

                $event = new FormEvent(
                    $form,
                    [
                        ContentType::BLOCK_PREFIX . 'foo' => 'bar',
                        ContentType::BLOCK_PREFIX . 'bar' => 'foo',
                        ContentType::BLOCK_PREFIX . 'foo2' => 'bar',
                    ]
                );
                $callable($event);

                return $builder;
            });

        $builder
            ->method('add')
            ->willReturnCallback(
                function (string|FormBuilderInterface $child, ?string $type, array $options = []) use ($builder): MockObject {
                    if (DocumentType::class == $type && isset($options['query_builder'])) {
                        $qBuilder = $this->createMock(Builder::class);
                        $qBuilder->expects($this->once())
                            ->method('field')
                            ->with('deletedAt')
                            ->willReturnSelf();

                        $qBuilder->expects($this->once())
                            ->method('equals')
                            ->with(null)
                            ->willReturnSelf();

                        $repository = $this->createMock(DocumentRepository::class);
                        $repository->expects($this->once())
                            ->method('createQueryBuilder')
                            ->willReturn($qBuilder);

                        $options['query_builder']($repository);
                    }

                    return $builder;
                }
            );

        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->buildForm($builder, ['doctrine_type' => DocumentType::class]));
    }

    public function testConfigureOptions(): void
    {
        $this->assertInstanceOf(AbstractType::class, $this->buildForm()->configureOptions(
            $this->createMock(OptionsResolver::class)
        ));
    }
}
