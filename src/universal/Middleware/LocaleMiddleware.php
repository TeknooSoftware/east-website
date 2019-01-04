<?php

declare(strict_types=1);

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

namespace Teknoo\East\Website\Middleware;

use Gedmo\Translatable\TranslatableListener;
use Psr\Http\Message\ServerRequestInterface;
use Teknoo\East\Foundation\Http\ClientInterface;
use Teknoo\East\Foundation\Manager\ManagerInterface;
use Teknoo\East\Foundation\Middleware\MiddlewareInterface;
use Teknoo\East\Foundation\Promise\Promise;
use Teknoo\East\Foundation\Session\SessionInterface;

/**
 * @license     http://teknoo.software/license/mit         MIT License
 * @author      Richard Déloge <richarddeloge@gmail.com>
 */
class LocaleMiddleware implements MiddlewareInterface
{
    public const SESSION_KEY = 'locale';
    public const MIDDLEWARE_PRIORITY = 6;

    /**
     * @var TranslatableListener
     */
    private $listenerTranslatable;

    /**
     * @var string
     */
    private $defaultLocale = 'en';

    /**
     * LocaleRouter constructor.
     * @param TranslatableListener $listenerTranslatable
     * @param string $defaultLocale
     */
    public function __construct(TranslatableListener $listenerTranslatable, string $defaultLocale = 'en')
    {
        $this->listenerTranslatable = $listenerTranslatable;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $locale
     * @return LocaleMiddleware
     */
    private function registerLocaleInSession(ServerRequestInterface $request, string $locale): LocaleMiddleware
    {
        $session = $request->getAttribute(SessionInterface::ATTRIBUTE_KEY);
        if ($session instanceof SessionInterface) {
            $session->set(static::SESSION_KEY, $locale);
        }

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    private function getLocaleFromSession(ServerRequestInterface &$request): string
    {
        $returnedLocale = $this->defaultLocale;
        $session = $request->getAttribute(SessionInterface::ATTRIBUTE_KEY);
        if ($session instanceof SessionInterface) {
            $session->get(
                static::SESSION_KEY,
                new Promise(
                    function (string $locale) use (&$request, &$returnedLocale) {
                        $this->listenerTranslatable->setTranslatableLocale($locale);
                        $request = $request->withAttribute('locale', $locale);
                        $returnedLocale = $locale;
                    },
                    function () use (&$request) {
                        $this->listenerTranslatable->setTranslatableLocale($this->defaultLocale);
                        $request = $request->withAttribute('locale', $this->defaultLocale);
                    }
                )
            );
        }

        return $returnedLocale;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    private function getViewParameters(ServerRequestInterface $request): array
    {
        return $request->getAttribute(ViewParameterInterface::REQUEST_PARAMETER_KEY, []);
    }

    /**
     * @param string $locale
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    private function updateViewParameters(string $locale, ServerRequestInterface $request): ServerRequestInterface
    {
        $parameters = $this->getViewParameters($request);
        $parameters['locale'] = $locale;

        return $request->withAttribute(ViewParameterInterface::REQUEST_PARAMETER_KEY, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(
        ClientInterface $client,
        ServerRequestInterface $request,
        ManagerInterface $manager
    ): MiddlewareInterface {
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['locale'])) {
            $locale = $queryParams['locale'];
            $this->listenerTranslatable->setTranslatableLocale($locale);
            $this->registerLocaleInSession($request, $locale);
            $request = $request->withAttribute('locale', $locale);
        } else {
            $locale = $this->getLocaleFromSession($request);
        }

        $request = $this->updateViewParameters($locale, $request);

        $manager->continueExecution($client, $request);

        return $this;
    }
}
