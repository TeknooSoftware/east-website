<?php

/*
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license
 * that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richard@teknoo.software so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 */

declare(strict_types=1);

namespace Teknoo\Tests\East\Website\Twig\Extension;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\DTO\ReadOnlyArray;
use Teknoo\East\Website\Twig\Extension\SanitizedContent;

/**
 * Class DefinitionProviderTest.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @covers      \Teknoo\East\Website\Twig\Extension\SanitizedContent
 */
class ExtendedSanitizedContentTest extends TestCase
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
        $content->expects(self::any())
            ->method('getSanitizedParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects(self::never())->method('sanitize');
        $sanitizer->expects(self::never())->method('sanitizeFor');

        $filter = new class($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

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
        $content->expects(self::any())
            ->method('getSanitizedParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects(self::never())->method('sanitize');
        $sanitizer->expects(self::never())->method('sanitizeFor');

        $filter = new class($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

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
        $content->expects(self::any())
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content->expects(self::any())
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects(self::never())->method('sanitize')->with('bar')->willReturn('bar1');
        $sanitizer->expects(self::once())->method('sanitizeFor')->with('default', '123bar')->willReturn('bar2');

        $filter = new class($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

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
        $content->expects(self::any())
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content->expects(self::any())
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $filter = new class(null) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

        self::assertEquals(
            '123bar',
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
        $content->expects(self::any())
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content->expects(self::any())
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects(self::once())->method('sanitize')->with('123bar')->willReturn('bar1');
        $sanitizer->expects(self::never())->method('sanitizeFor')->with('default', 'bar')->willReturn('bar2');

        $filter = new class($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

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
        $content->expects(self::any())
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content->expects(self::any())
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects(self::never())->method('sanitize')->with('hello')->willReturn('bar1');
        $sanitizer->expects(self::once())->method('sanitizeFor')->with('default', '123hello')->willReturn('bar2');

        $filter = new class($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

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
        $content->expects(self::any())
            ->method('getSanitizedParts')
            ->willReturn(null);

        $content->expects(self::any())
            ->method('getParts')
            ->willReturn(new ReadOnlyArray(['foo' => 'bar']));

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects(self::once())->method('sanitize')->with('123hello')->willReturn('bar1');
        $sanitizer->expects(self::never())->method('sanitizeFor')->with('default', 'hello')->willReturn('bar2');

        $filter = new class($sanitizer) extends SanitizedContent {
            protected function hook(string $data): string
            {
                return '123' . $data;
            }
        };

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
