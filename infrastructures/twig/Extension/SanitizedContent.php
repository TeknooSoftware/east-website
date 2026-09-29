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

namespace Teknoo\East\Website\Twig\Extension;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Teknoo\East\Website\Object\Content;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig filter to fetch an element in a content's part, prior into the sanitized content, else take it from not
 * sanitized content and sanitize it. If the element is not present, a default value can be defined
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (https://deloge.io - richard@deloge.io)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software - contact@teknoo.software)
 * @license     http://teknoo.software/license/bsd-3         3-Clause BSD License
 * @author      Richard Déloge <richard@teknoo.software>
 */
class SanitizedContent extends AbstractExtension
{
    public function __construct(
        private readonly ?object $sanitizer = null,
    ) {
    }

    /**
     * @return array<TwigFilter>
     */
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'sanitized_part',
                $this->getPart(...),
                [
                    'is_safe' => ['html'],
                ],
            ),
        ];
    }

    protected function hook(string $data): string
    {
        return $data;
    }

    public function getPart(
        Content $content,
        string $partName,
        string $salt,
        string $default = '',
        string $sanitizeContext = '',
    ): string {
        $sanitizedPart = $content->getSanitizedParts($salt);

        if (null !== $sanitizedPart) {
            return $sanitizedPart[$partName] ?? $default;
        }

        $value = $this->hook($content->getParts()[$partName] ?? $default);
        if (!$this->sanitizer instanceof HtmlSanitizerInterface) {
            return $value;
        }

        if (!empty($sanitizeContext)) {
            return $this->sanitizer->sanitizeFor($sanitizeContext, $value);
        }

        return $this->sanitizer->sanitize($value);
    }
}
