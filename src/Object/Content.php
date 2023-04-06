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

namespace Teknoo\East\Website\Object;

use DateTimeInterface;
use Exception;
use Stringable;
use Teknoo\East\Common\Contracts\Object\DeletableInterface;
use Teknoo\East\Common\Contracts\Object\IdentifiedObjectInterface;
use Teknoo\East\Common\Contracts\Object\PublishableInterface;
use Teknoo\East\Common\Contracts\Object\SluggableInterface;
use Teknoo\East\Common\Contracts\Object\TimestampableInterface;
use Teknoo\East\Common\Object\User;
use Teknoo\East\Website\Contracts\Object\TranslatableInterface;
use Teknoo\East\Common\Contracts\Loader\LoaderInterface;
use Teknoo\East\Website\Object\Content\Draft;
use Teknoo\East\Website\Object\Content\Published;
use Teknoo\East\Common\Service\FindSlugService;
use Teknoo\East\Website\Object\DTO\ReadOnlyArray;
use Teknoo\States\Automated\Assertion\AssertionInterface;
use Teknoo\States\Automated\Assertion\Property;
use Teknoo\States\Automated\Assertion\Property\IsInstanceOf;
use Teknoo\States\Automated\Assertion\Property\IsNotInstanceOf;
use Teknoo\States\Automated\AutomatedInterface;
use Teknoo\States\Automated\AutomatedTrait;
use Teknoo\States\Proxy\ProxyTrait;

use function hash;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Stated class representing a dynamic content in the website. The content has a `Type` with several blocks.
 * Block's values are stored in object of this class in `parts`.
 * Object of this class can be translated.
 *
 * @copyright   Copyright (c) EIRL Richard Déloge (richard@teknoo.software)
 * @copyright   Copyright (c) SASU Teknoo Software (https://teknoo.software)
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richard@teknoo.software>
 *
 * @implements SluggableInterface<IdentifiedObjectInterface>
 */
class Content implements
    IdentifiedObjectInterface,
    TranslatableInterface,
    AutomatedInterface,
    DeletableInterface,
    PublishableInterface,
    TimestampableInterface,
    SluggableInterface,
    Stringable
{
    use PublishableTrait;
    use AutomatedTrait;
    use ProxyTrait;

    protected const HASH_ALGO_FOR_SANITIZED = 'sha256';

    protected ?User $author = null;

    protected string $title = '';

    protected string $subtitle = '';

    protected ?string $slug = null;

    protected ?Type $type = null;

    protected string $parts = '{}';

    protected ?ReadOnlyArray $decodedParts = null;

    protected string $sanitizedParts = '{}';

    protected ?ReadOnlyArray $decodedSanitizedParts = null;

    protected string $sanitizedHash = '';

    /**
     * @var array<string>
     */
    protected array $tags = [];

    protected ?string $description = null;

    protected ?string $localeField = null;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->initializeStateProxy();
        $this->updateStates();
    }

    /**
     * @return array<string>
     */
    public static function statesListDeclaration(): array
    {
        return [
            Draft::class,
            Published::class,
        ];
    }

    /**
     * @return array<AssertionInterface>
     */
    protected function listAssertions(): array
    {
        return [
            (new Property([Draft::class]))->with('publishedAt', new IsNotInstanceOf(DateTimeInterface::class)),
            (new Property([Published::class]))->with('publishedAt', new IsInstanceOf(DateTimeInterface::class)),
        ];
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): Content
    {
        $this->author = $author;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(?string $title): Content
    {
        $this->title = (string) $title;

        return $this;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }

    public function getSubtitle(): string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle = null): Content
    {
        $this->subtitle = (string) $subtitle;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function prepareSlugNear(
        LoaderInterface $loader,
        FindSlugService $findSlugService,
        string $slugField
    ): SluggableInterface {
        $slugValue = $this->getSlug();
        if (empty($slugValue)) {
            $slugValue = $this->getTitle();
        }

        $findSlugService->process(
            $loader,
            $slugField,
            $this,
            [
                $slugValue
            ]
        );

        return $this;
    }

    public function setSlug(?string $slug): Content
    {
        $this->slug = $slug;

        return $this;
    }

    public function getType(): ?Type
    {
        return $this->type;
    }

    public function setType(?Type $type): Content
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return ReadOnlyArray
     */
    public function getParts(): ReadOnlyArray
    {
        if (null !== $this->decodedParts) {
            return $this->decodedParts;
        }

        return $this->decodedParts = new ReadOnlyArray(
            (array) json_decode(
                json: (string) $this->parts,
                associative: true,
                depth: 512,
                flags: JSON_THROW_ON_ERROR,
            )
        );
    }

    /**
     * @return ReadOnlyArray|null
     */
    public function getSanitizedParts(string $salt): ?ReadOnlyArray
    {
        if (null !== $this->decodedSanitizedParts) {
            return $this->decodedSanitizedParts;
        }

        if ($this->computeSanitizedHash($this->sanitizedParts, $salt) !== $this->sanitizedHash) {
            return null;
        }

        return $this->decodedSanitizedParts = new ReadOnlyArray(
            (array) json_decode(
                json: (string) $this->sanitizedParts,
                associative: true,
                depth: 512,
                flags: JSON_THROW_ON_ERROR,
            )
        );
    }

    /**
     * @param array<mixed>|null $parts
     */
    public function setParts(?array $parts): Content
    {
        $this->decodedParts = null;
        $this->parts = (string) json_encode((array) $parts, JSON_THROW_ON_ERROR);

        return $this;
    }

    private function computeSanitizedHash(string &$value, string &$salt): string
    {
        return hash(
            algo: self::HASH_ALGO_FOR_SANITIZED,
            data: $salt . $value,
        );
    }

    /**
     * @param array<mixed>|null $parts
     */
    public function setSanitizedParts(?array $parts, string $salt): Content
    {
        $this->decodedSanitizedParts = null;
        $this->sanitizedParts = (string) json_encode((array) $parts, JSON_THROW_ON_ERROR);
        $this->sanitizedHash = $this->computeSanitizedHash($this->sanitizedParts, $salt);

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param array<string> $tags
     */
    public function setTags(array $tags): Content
    {
        $this->tags = $tags;

        return $this;
    }

    public function getDescription(): string
    {
        return (string) $this->description;
    }

    public function setDescription(?string $description): Content
    {
        $this->description = $description;

        return $this;
    }

    public function getLocaleField(): ?string
    {
        return $this->localeField;
    }

    public function setLocaleField(?string $localeField): TranslatableInterface
    {
        $this->localeField = $localeField;

        return $this;
    }

    public function setPublishedAt(DateTimeInterface $dateTime): PublishableInterface
    {
        return $this->publishingAt($dateTime);
    }
}
