<?php

/**
 * East Website.
 *
 * LICENSE
 *
 * This source file is subject to the MIT license and the version 3 of the GPL3
 * license that are bundled with this package in the folder licences
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to richarddeloge@gmail.com so we can send you a copy immediately.
 *
 *
 * @copyright   Copyright (c) 2009-2017 Richard Déloge (richarddeloge@gmail.com)
 *
 * @link        http://teknoo.software/east/website Project website
 *
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */

namespace Teknoo\East\Website\EndPoint;

use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Website\Loader\ContentLoader;
use Teknoo\East\Website\Object\Content;
use Teknoo\East\Website\Service\MenuGenerator;

trait ContentEndPointTrait
{
    /**
     * @var ContentLoader
     */
    private $contentLoader;

    /**
     * @var MenuGenerator
     */
    private $menuGenerator;

    /**
     * @param ServerRequestInterface $request
     * @return string[]
     */
    private function parseUrl(ServerRequestInterface $request): array
    {
        return \explode('\\', \trim('/', $request->getUri()));
    }

    /**
     * @param ServerRequestInterface $request
     * @param ClientInterface $client
     * @return self
     */
    public function display(
        ServerRequestInterface $request,
        ClientInterface $client
    ) {

        $urlParts = $this->parseUrl($request);
        $contentSlug = \array_pop($urlParts);

        $this->contentLoader->bySlug(
            $contentSlug,
            new Promise(
                function (Content $content) use ($client) {
                    $type = $content->getType();
                    $this->render(
                        $client,
                        $type->getTemplate(), [
                        'content' => $content,
                        'menuGenerator' => $this->menuGenerator,
                    ]);
                },
                function (\Throwable $e) use ($client) {
                    $client->errorInRequest($e);
                }
            )
        );

        return $this;
    }
}