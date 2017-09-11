<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Middleware\Internationalization\Gettext;

use Eureka\Component\Config\Config;
use Eureka\Component\Container\Container;
use Eureka\Component\Http\Message\Response;
use Eureka\Component\Locales;
use Eureka\Component\Psr\Http\Middleware\DelegateInterface;
use Eureka\Component\Psr\Http\Middleware\ServerMiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class GettextMiddleware implements ServerMiddlewareInterface
{
    /** @var Routing\RouteCollection $collection */
    private $collection = null;

    /** @var Config $config */
    private $config = null;

    /**
     * Class constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param  ServerRequestInterface  $request
     * @param  DelegateInterface $frame
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $frame)
    {
        $i18n = $this->config->get('global.i18n');
        $uri  = $request->getUri();

        //~ Lang
        $lang = substr($uri->getPath(), 1, 2);

        // If not active, redirect to the main lang
        if (!in_array($lang, $i18n['langs'])) {
            $lang = 'fr';

            return $this->redirect($request, $uri->withPath('/' . $lang . '/', substr($uri->getPath(), 4)));
        }
        define('EKA_LANG', $lang);
        $locale = $i18n['locales'][$lang];

        //~ Set locale & use it for messages (translations).
        $locale = new Locales\Locale([$locale]);
        $locale->useLocale(LC_MESSAGES);

        $domain = new Locales\Gettext\Domain('allinwedding', $i18n['path']);
        $domain->useDomain();

        $request = $request->withAttribute('lang', $lang);

        return $frame->next($request);
    }

    /**
     * Build response to redirect to the given url
     *
     * @param  RequestInterface $request
     * @param  UriInterface $url
     * @param  int $status
     * @return ResponseInterface
     */
    private function redirect(RequestInterface $request, UriInterface $url, $status = 301)
    {
        $params   = $request->getServerParams();
        $protocol = empty($params['SERVER_PROTOCOL']) ? 'HTTP/1.0' : $params['SERVER_PROTOCOL'];

        return (new Response())->withStatus($status)
            ->withProtocolVersion($request->getProtocolVersion())
            ->withHeader('Location' ,(string) $url);
    }
}
