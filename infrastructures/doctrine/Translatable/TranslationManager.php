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

namespace Teknoo\East\Website\Doctrine\Translatable;

use Teknoo\East\Website\Contracts\DBSource\TranslationManagerInterface;
use Teknoo\East\Website\Doctrine\Translatable\Persistence\Adapter\ODM as ODMPersistence;

/**
 * Translation manager to enable or disable deferred translations loading
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class TranslationManager implements TranslationManagerInterface
{
    public function __construct(
        private readonly ODMPersistence $persistence,
    ) {
    }

    public function deferringTranslationsLoading(): TranslationManagerInterface
    {
        $this->persistence->setDeferred(true);

        return $this;
    }

    public function stopDeferringTranslationsLoading(): TranslationManagerInterface
    {
        $this->persistence->executeAllDeferredLoadings();
        $this->persistence->setDeferred(false);

        return $this;
    }
}
