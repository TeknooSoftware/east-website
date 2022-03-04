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
 * @copyright   Copyright (c) 2009-2021 EIRL Richard Déloge (richarddeloge@gmail.com)
 * @copyright   Copyright (c) 2020-2021 SASU Teknoo Software (https://teknoo.software)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

declare(strict_types=1);

namespace Teknoo\East\Website\Object;

use Teknoo\Immutable\ImmutableInterface;
use Teknoo\Immutable\ImmutableTrait;

/**
 * Class to create embedded object into a media instance to host major metadata of a media (content type, file name,
 * alternative name, etc...)
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class MediaMetadata implements ImmutableInterface
{
    use ImmutableTrait;

    public function __construct(
        private readonly string $contentType,
        private readonly string $fileName = '',
        private readonly string $alternative = '',
        private readonly string $localPath = '',
        private readonly string $legacyId = ''
    ) {
        $this->uniqueConstructorCheck();
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getAlternative(): string
    {
        return $this->alternative;
    }

    public function getLocalPath(): string
    {
        return $this->localPath;
    }

    public function getLegacyId(): string
    {
        return $this->legacyId;
    }
}
