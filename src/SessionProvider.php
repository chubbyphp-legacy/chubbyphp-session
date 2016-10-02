<?php

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
        $container['session.expirationTime'] = 1200;
        $container['session.privateRsaKey'] = '';
        $container['session.publicRsaKey'] = '';

        $container['session.setCookieHttpOnly'] = true;
        $container['session.setCookiePath'] = '/';
        $container['session.setCookieSecureOnly'] = true;

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

        $container['session.setCookie'] = function () use ($container) {
            return SetCookie::create(SessionMiddleware::DEFAULT_COOKIE)
                ->withHttpOnly($container['session.setCookieHttpOnly'])
                ->withPath($container['session.setCookiePath'])
                ->withSecure($container['session.setCookieSecureOnly'])
            ;
        };

        $container['session'] = function () {
            return new Session();
        };
    }
}
