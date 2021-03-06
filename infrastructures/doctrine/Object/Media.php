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

namespace Teknoo\East\Website\Doctrine\Object;

use DateTimeInterface;
use Teknoo\East\Website\Object\Media as OriginalMedia;

/**
 * Media specialization in doctrine, present originally to support new GirdFS implementation in Doctrine ODM
 * Present to avoid bc break.
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class Media extends OriginalMedia
{
    private ?DateTimeInterface $uploadDate = null;

    private ?int $chunkSize = null;

    public function getChunkSize(): ?int
    {
        return $this->chunkSize;
    }

    public function getUploadDate(): ?DateTimeInterface
    {
        return $this->uploadDate;
    }
}
