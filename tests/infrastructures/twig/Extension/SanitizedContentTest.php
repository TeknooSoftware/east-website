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
 * @license     https://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 */
#[CoversClass(SanitizedContent::class)]
class SanitizedContentTest extends TestCase
{
    public function testGetFilters()
    {
        self::assertIsArray(
            (new SanitizedContent)->getFilters()
        );
    }

    public function testGetPartSanitizedPartsKeyFound()
    {
        $content = $this->createMock(Content::class);
        $content->expects($this->any())
            ->method('getSanitizedParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize');
        $sanitizer->expects($this->never())->method('sanitizeFor');

        $filter = new SanitizedContent($sanitizer);
        self::assertEquals(
            'bar',
            $filter->getPart(
                $content,
                'foo',
                'baba',
                'hello',
                'default'
            )
        );
    }

    public function testGetPartSanitizedPartsKeyNotFound()
    {
        $content = $this->createMock(Content::class);
        $content->expects($this->any())
            ->method('getSanitizedParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize');
        $sanitizer->expects($this->never())->method('sanitizeFor');

        $filter = new SanitizedContent($sanitizer);
        self::assertEquals(
            'hello',
            $filter->getPart(
                $content,
                'bar',
                'baba',
                'hello',
                'default'
            )
        );
    }

    public function testGetPartNotSanitizedPartsKeyFoundWithContext()
    {
        $content = $this->createMock(Content::class);
        $content->expects($this->any())
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content->expects($this->any())
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize')->with('bar')->willReturn('bar1');
        $sanitizer->expects($this->once())->method('sanitizeFor')->with('default', 'bar')->willReturn('bar2');

        $filter = new SanitizedContent($sanitizer);
        self::assertEquals(
            'bar2',
            $filter->getPart(
                $content,
                'foo',
                'baba',
                'hello',
                'default'
            )
        );
    }

    public function testGetPartNotSanitizedWithoutSanitizer()
    {
        $content = $this->createMock(Content::class);
        $content->expects($this->any())
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content->expects($this->any())
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $filter = new SanitizedContent(null);
        self::assertEquals(
            'bar',
            $filter->getPart(
                $content,
                'foo',
                'baba',
                'hello',
                'default'
            )
        );
    }

    public function testGetPartNotSanitizedPartsKeyFoundWithoutContext()
    {
        $content = $this->createMock(Content::class);
        $content->expects($this->any())
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content->expects($this->any())
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->once())->method('sanitize')->with('bar')->willReturn('bar1');
        $sanitizer->expects($this->never())->method('sanitizeFor')->with('default', 'bar')->willReturn('bar2');

        $filter = new SanitizedContent($sanitizer);
        self::assertEquals(
            'bar1',
            $filter->getPart(
                $content,
                'foo',
                'baba',
                'hello'
            )
        );
    }

    public function testGetPartNotSanitizedPartsKeyNotFoundWithContext()
    {
        $content = $this->createMock(Content::class);
        $content->expects($this->any())
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content->expects($this->any())
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize')->with('hello')->willReturn('bar1');
        $sanitizer->expects($this->once())->method('sanitizeFor')->with('default', 'hello')->willReturn('bar2');

        $filter = new SanitizedContent($sanitizer);
        self::assertEquals(
            'bar2',
            $filter->getPart(
                $content,
                'bar',
                'baba',
                'hello',
                'default'
            )
        );
    }

    public function testGetPartNotSanitizedPartsKeyNotFoundWithoutContext()
    {
        $content = $this->createMock(Content::class);
        $content->expects($this->any())
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content->expects($this->any())
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->once())->method('sanitize')->with('hello')->willReturn('bar1');
        $sanitizer->expects($this->never())->method('sanitizeFor')->with('default', 'hello')->willReturn('bar2');

        $filter = new SanitizedContent($sanitizer);
        self::assertEquals(
            'bar1',
            $filter->getPart(
                $content,
                'bar',
                'baba',
                'hello'
            )
        );
    }
}
