<?php

namespace Chubbyphp\Tests\Translation;

use Chubbyphp\Session\Session;
use Chubbyphp\Session\SessionProvider;
use Dflydev\FigCookies\SetCookie;
use Pimple\Container;
use PSR7Session\Http\SessionMiddleware;

/**
 * @covers Chubbyphp\Session\SessionProvider
 */
final class SessionProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $container = new Container();
        $container->register(new SessionProvider());

        self::assertTrue(isset($container['session.expirationTime']));
        self::assertTrue(isset($container['session.privateRsaKey']));
        self::assertTrue(isset($container['session.publicRsaKey']));

        self::assertTrue(isset($container['session.setCookieHttpOnly']));
        self::assertTrue(isset($container['session.setCookiePath']));
        self::assertTrue(isset($container['session.setCookieSecureOnly']));

        self::assertTrue(isset($container['session.middleware']));
        self::assertTrue(isset($container['session.setCookie']));
        self::assertTrue(isset($container['session']));

        self::assertSame(1200, $container['session.expirationTime']);
        self::assertSame('', $container['session.privateRsaKey']);
        self::assertSame('', $container['session.publicRsaKey']);

        self::assertTrue($container['session.setCookieHttpOnly']);
        self::assertSame('/', $container['session.setCookiePath']);
        self::assertTrue($container['session.setCookieSecureOnly']);

        self::assertInstanceOf(SessionMiddleware::class, $container['session.middleware']);
        self::assertInstanceOf(SetCookie::class, $container['session.setCookie']);
        self::assertInstanceOf(Session::class, $container['session']);
    }
}
