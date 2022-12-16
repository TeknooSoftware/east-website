<?php

/*
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
 * @copyright   Copyright (c) EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
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
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class SanitizedContent extends AbstractExtension
{
    public function __construct(
        private readonly ?HtmlSanitizerInterface $sanitizer = null,
    ) {
    }

    /**
     * @return array<TwigFilter>
     */
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
        if (null === $this->sanitizer) {
            return $value;
        }

        if (!empty($sanitizeContext)) {
            return $this->sanitizer->sanitizeFor($sanitizeContext, $value);
        }

        return $this->sanitizer->sanitize($value);
    }
}
