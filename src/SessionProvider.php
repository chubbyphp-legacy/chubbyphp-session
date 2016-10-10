<?php

declare(strict_types=1);

namespace Chubbyphp\Session;

use Dflydev\FigCookies\SetCookie;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use PSR7Session\Http\SessionMiddleware;
use PSR7Session\Time\SystemCurrentTime;

final class SessionProvider implements ServiceProviderInterface
{
    /**
     * @param Container $container
     */
    public function register(Container $container)
    {
        $this->registerSettings($container);
        $this->registerSetCookie($container);
        $this->registerMiddleware($container);

        $container['session'] = function () {
            return new Session();
        };
    }

    /**
     * @param Container $container
     */
    private function registerSettings(Container $container)
    {
        $container['session.expirationTime'] = 1200;
        $container['session.privateRsaKey'] = '';
        $container['session.publicRsaKey'] = '';

        $container['session.setCookieHttpOnly'] = true;
        $container['session.setCookiePath'] = '/';
        $container['session.setCookieSecureOnly'] = true;
    }

    /**
     * @param Container $container
     */
    private function registerSetCookie(Container $container)
    {
        $container['session.setCookie'] = function () use ($container) {
            return SetCookie::create(SessionMiddleware::DEFAULT_COOKIE)
                ->withHttpOnly($container['session.setCookieHttpOnly'])
                ->withPath($container['session.setCookiePath'])
                ->withSecure($container['session.setCookieSecureOnly'])
                ;
        };
    }

    /**
     * @param Container $container
     */
    private function registerMiddleware(Container $container)
    {
        $container['session.middleware'] = function () use ($container) {
            return new SessionMiddleware(
                new Sha256(),
                $container['session.privateRsaKey'],
                $container['session.publicRsaKey'],
                $container['session.setCookie'],
                new Parser(),
                $container['session.expirationTime'],
                new SystemCurrentTime()
            );
        };
    }
}
