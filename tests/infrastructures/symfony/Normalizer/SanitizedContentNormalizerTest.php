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

namespace Teknoo\Tests\East\WebsiteBundle\Normalizer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Teknoo\East\FoundationBundle\Normalizer\EastNormalizer;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Object\Post;
use Teknoo\East\WebsiteBundle\Normalizer\SanitizedContentNormalizer;

/**
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
#[CoversClass(SanitizedContentNormalizer::class)]
class SanitizedContentNormalizerTest extends TestCase
{
    private function buildNormalizer(
        ?HtmlSanitizerInterface $sanitizer = null,
        ?string $sanitizeContext = null,
    ): SanitizedContentNormalizer {
        return new SanitizedContentNormalizer(
            new EastNormalizer(),
            $sanitizer,
            'fooBar',
            $sanitizeContext,
        );
    }

    private function buildContent(): Content
    {
        $content = (new Content())
            ->setTitle('Title')
            ->setSlug('title')
            ->setParts(['body' => '<script>alert(1)</script>Hello']);
        $content->setId('c1');

        return $content;
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = $this->buildNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new Content(), 'json', ['groups' => ['public']]));
        $this->assertTrue($normalizer->supportsNormalization(new Post(), 'json', ['groups' => ['public', 'foo']]));
        $this->assertFalse($normalizer->supportsNormalization(new Content(), 'json', ['groups' => ['crud']]));
        $this->assertFalse($normalizer->supportsNormalization(new Content(), 'json'));
        $this->assertFalse($normalizer->supportsNormalization(new stdClass(), 'json', ['groups' => ['public']]));
    }

    public function testGetSupportedTypes(): void
    {
        $this->assertEquals(
            [Content::class => false],
            $this->buildNormalizer()->getSupportedTypes('json'),
        );
    }

    public function testNormalizeWithStoredSanitizedParts(): void
    {
        $content = $this->buildContent()->setSanitizedParts(['body' => 'Hello'], 'fooBar');

        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize');
        $sanitizer->expects($this->never())->method('sanitizeFor');

        $normalized = $this->buildNormalizer($sanitizer)->normalize($content, 'json', ['groups' => ['public']]);

        $this->assertEquals(Content::class, $normalized['@class']);
        $this->assertEquals('c1', $normalized['id']);
        $this->assertEquals('Title', $normalized['title']);
        $this->assertEquals(['body' => 'Hello'], $normalized['parts']);
    }

    public function testNormalizeWithPartsSanitizedOnTheFly(): void
    {
        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->once())
            ->method('sanitize')
            ->with('<script>alert(1)</script>Hello')
            ->willReturn('Hello');
        $sanitizer->expects($this->never())->method('sanitizeFor');

        $normalized = $this->buildNormalizer($sanitizer)
            ->normalize($this->buildContent(), 'json', ['groups' => ['public']]);

        $this->assertEquals(['body' => 'Hello'], $normalized['parts']);
    }

    public function testNormalizeWithPartsSanitizedOnTheFlyWithAContext(): void
    {
        $sanitizer = $this->createMock(HtmlSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize');
        $sanitizer->expects($this->once())
            ->method('sanitizeFor')
            ->with('body', '<script>alert(1)</script>Hello')
            ->willReturn('Hello');

        $normalized = $this->buildNormalizer($sanitizer, 'body')
            ->normalize($this->buildContent(), 'json', ['groups' => ['public']]);

        $this->assertEquals(['body' => 'Hello'], $normalized['parts']);
    }

    public function testNormalizeWithoutSanitizer(): void
    {
        $normalized = $this->buildNormalizer()
            ->normalize($this->buildContent(), 'json', ['groups' => ['public']]);

        $this->assertEquals(['body' => '<script>alert(1)</script>Hello'], $normalized['parts']);
    }
}
