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

namespace Teknoo\East\WebsiteBundle\Normalizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Teknoo\East\FoundationBundle\Normalizer\EastNormalizer;
use Teknoo\East\Website\Object\Content;

use function in_array;

/**
 * Symfony normalizer to normalize contents (and posts) for the public API (with the normalization group `public`),
 * with their sanitized parts, in one pass. Contents are normalized by the East Normalizer. Parts are sanitized like
 * in HTML pages: sanitized parts stored with a valid hash are used, else raw parts are sanitized on the fly, with the
 * default sanitize context (like in the form `ContentType`).
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SanitizedContentNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly EastNormalizer $eastNormalizer,
        private readonly ?HtmlSanitizerInterface $sanitizer,
        private readonly string $salt,
        private readonly ?string $sanitizeContext = null,
    ) {
    }

    private function sanitize(string $value): string
    {
        if (null === $this->sanitizer) {
            return $value;
        }

        if (!empty($this->sanitizeContext)) {
            return $this->sanitizer->sanitizeFor($this->sanitizeContext, $value);
        }

        return $this->sanitizer->sanitize($value);
    }

    /**
     * @return array<string, string>
     */
    private function getSanitizedParts(Content $content): array
    {
        $sanitizedParts = $content->getSanitizedParts($this->salt);
        if (null !== $sanitizedParts) {
            return $sanitizedParts->toArray();
        }

        $parts = [];
        foreach ($content->getParts()->toArray() as $name => $value) {
            $parts[$name] = $this->sanitize($value);
        }

        return $parts;
    }

    /**
     * @param array<string, string[]> $context
     * @return array<int|string, mixed>
     */
    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): array {
        $normalized = $this->eastNormalizer->normalize($data, $format, $context);

        if ($data instanceof Content) {
            $normalized['parts'] = $this->getSanitizedParts($data);
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(
        mixed $data,
        ?string $format = null,
        array $context = [],
    ): bool {
        return $data instanceof Content
            && in_array('public', (array) ($context['groups'] ?? []), true);
    }

    /**
     * @return array<class-string|'*'|'object'|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        //Not cacheable, the support depends on the normalization's context
        return [
            Content::class => false,
        ];
    }
}
