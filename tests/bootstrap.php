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

date_default_timezone_set('UTC');

error_reporting(E_ALL | E_STRICT);

ini_set('memory_limit', '128M');

include __DIR__ . '/fakeQuery.php';
include __DIR__ . '/fakeUOW.php';
include __DIR__ . '/fakeRuntimeException.php';
include __DIR__ . '/fakeObjectId.php';
include __DIR__ . '/fakeLegacyPasswordAuthenticatedUserInterface.php';
include __DIR__.'/../vendor/autoload.php';

if (!\class_exists(\MongoGridFSFile::class)) {
    /*
     * To avoid error on test where mongodb lib is not installed
     */
    class MongoGridFSFile
    {
        public function getSize()
        {
        }
    }
}

