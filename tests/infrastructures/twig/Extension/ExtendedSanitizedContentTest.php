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

namespace Teknoo\Tests\East\Website\Twig\Extension;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\DTO\ReadOnlyArray;
use Teknoo\East\Website\Twig\Extension\SanitizedContent;

/**
 * Class DefinitionProviderTest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(SanitizedContent::class)]
class ExtendedSanitizedContentTest extends TestCase
{
    public function testGetFilters(): void
    {
        $this->assertIsArray((new SanitizedContent())->getFilters());
    }

    public function testGetPartSanitizedPartsKeyFound(): void
    {
        $content = $this->createStub(Content::class);
        $content
            ->method('getSanitizedParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize');
        $sanitizer->expects($this->never())->method('sanitizeFor');

        $filter = new class ($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

        $this->assertEquals('bar', $filter->getPart(
            $content,
            'foo',
            'baba',
            'hello',
            'default'
        ));
    }

    public function testGetPartSanitizedPartsKeyNotFound(): void
    {
        $content = $this->createStub(Content::class);
        $content
            ->method('getSanitizedParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize');
        $sanitizer->expects($this->never())->method('sanitizeFor');

        $filter = new class ($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

        $this->assertEquals('hello', $filter->getPart(
            $content,
            'bar',
            'baba',
            'hello',
            'default'
        ));
    }

    public function testGetPartNotSanitizedPartsKeyFoundWithContext(): void
    {
        $content = $this->createStub(Content::class);
        $content
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize')->with('bar')->willReturn('bar1');
        $sanitizer->expects($this->once())->method('sanitizeFor')->with('default', '123bar')->willReturn('bar2');

        $filter = new class ($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

        $this->assertEquals('bar2', $filter->getPart(
            $content,
            'foo',
            'baba',
            'hello',
            'default'
        ));
    }

    public function testGetPartNotSanitizedWithoutSanitizer(): void
    {
        $content = $this->createStub(Content::class);
        $content
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $filter = new class (null) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

        $this->assertEquals('123bar', $filter->getPart(
            $content,
            'foo',
            'baba',
            'hello',
            'default'
        ));
    }

    public function testGetPartNotSanitizedPartsKeyFoundWithoutContext(): void
    {
        $content = $this->createStub(Content::class);
        $content
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->once())->method('sanitize')->with('123bar')->willReturn('bar1');
        $sanitizer->expects($this->never())->method('sanitizeFor')->with('default', 'bar')->willReturn('bar2');

        $filter = new class ($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

        $this->assertEquals('bar1', $filter->getPart(
            $content,
            'foo',
            'baba',
            'hello'
        ));
    }

    public function testGetPartNotSanitizedPartsKeyNotFoundWithContext(): void
    {
        $content = $this->createStub(Content::class);
        $content
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize')->with('hello')->willReturn('bar1');
        $sanitizer->expects($this->once())->method('sanitizeFor')->with('default', '123hello')->willReturn('bar2');

        $filter = new class ($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

        $this->assertEquals('bar2', $filter->getPart(
            $content,
            'bar',
            'baba',
            'hello',
            'default'
        ));
    }

    public function testGetPartNotSanitizedPartsKeyNotFoundWithoutContext(): void
    {
        $content = $this->createStub(Content::class);
        $content
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->once())->method('sanitize')->with('123hello')->willReturn('bar1');
        $sanitizer->expects($this->never())->method('sanitizeFor')->with('default', 'hello')->willReturn('bar2');

        $filter = new class ($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

        $this->assertEquals('bar1', $filter->getPart(
            $content,
            'bar',
            'baba',
            'hello'
        ));
    }
}
