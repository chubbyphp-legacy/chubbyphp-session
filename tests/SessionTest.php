<?php

namespace Chubbyphp\Tests\Translation;

use Chubbyphp\Session\FlashMessage;
use Chubbyphp\Session\Session;
use Psr\Http\Message\ServerRequestInterface as Request;
use PSR7Session\Http\SessionMiddleware;
use PSR7Session\Session\SessionInterface as PSR7Session;

/**
 * @covers Chubbyphp\Session\Session
 */
final class SessionTest extends \PHPUnit_Framework_TestCase
{
    public function testGetWithValue()
    {
        $expectedValue = ['key' => 'value'];

        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([
                'some.existing.key' => json_encode($expectedValue),
            ]),
        ]);

        $session = new Session();

        self::assertSame($expectedValue, $session->get($request, 'some.existing.key'));
    }

    public function testGetWithDefault()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([]),
        ]);

        $session = new Session();

        self::assertNull($session->get($request, 'some.existing.key'));
    }

    public function testHasWithValue()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([
                'some.existing.key' => json_encode(['key' => 'value']),
            ]),
        ]);

        $session = new Session();

        self::assertTrue($session->has($request, 'some.existing.key'));
    }

    public function testHasWithDefault()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([]),
        ]);

        $session = new Session();

        self::assertFalse($session->has($request, 'some.existing.key'));
    }

    public function testSet()
    {
        $expectedValue = ['key' => 'value'];

        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([]),
        ]);

        $session = new Session();

        self::assertSame(null, $session->get($request, 'some.existing.key'));

        $session->set($request, 'some.existing.key', $expectedValue);

        self::assertSame($expectedValue, $session->get($request, 'some.existing.key'));
    }

    public function testRemove()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([
                'some.existing.key' => json_encode(['key' => 'value']),
            ]),
        ]);

        $session = new Session();

        self::assertTrue($session->has($request, 'some.existing.key'));

        $session->remove($request, 'some.existing.key');

        self::assertFalse($session->has($request, 'some.existing.key'));
    }

    public function testAddFlash()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([]),
        ]);

        $flashMessage = new FlashMessage(FlashMessage::TYPE_SUCCESS, 'Worked like a charm');

        $session = new Session();

        self::assertFalse($session->has($request, Session::FLASH_KEY));

        $session->addFlash($request, $flashMessage);

        self::assertTrue($session->has($request, Session::FLASH_KEY));
    }

    public function testGetFlash()
    {
        $expectedFlashMessage = [
            FlashMessage::JSON_KEY_TYPE => FlashMessage::TYPE_SUCCESS,
            FlashMessage::JSON_KEY_MESSAGE => 'Worked like a charm',
        ];

        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([
                Session::FLASH_KEY => json_encode($expectedFlashMessage),
            ]),
        ]);

        $session = new Session();

        self::assertTrue($session->has($request, Session::FLASH_KEY));

        $flashMessage = $session->getFlash($request);

        self::assertSame($expectedFlashMessage, $flashMessage->jsonSerialize());

        self::assertFalse($session->has($request, Session::FLASH_KEY));
    }

    public function testGetNullFlash()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([]),
        ]);

        $session = new Session();

        self::assertNull($session->getFlash($request));
    }

    /**
     * @return Request|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getRequest(array $attributes)
    {
        $request = $this
            ->getMockBuilder(Request::class)
            ->setMethods(['getAttribute'])
            ->getMockForAbstractClass()
        ;

        $request->__attributes = $attributes;

        $request
            ->expects(self::any())
            ->method('getAttribute')
            ->willReturnCallback(function (string $name, $default = null) use ($request) {
                if (isset($request->__attributes[$name])) {
                    return $request->__attributes[$name];
                }

                return $default;
            })
        ;

        return $request;
    }

    /**
     * @param array $data
     *
     * @return PSR7Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPSR7Session(array $data = [])
    {
        $psr7Session = $this
            ->getMockBuilder(PSR7Session::class)
            ->setMethods(['get', 'has', 'set', 'remove'])
            ->getMockForAbstractClass()
        ;

        $psr7Session->__data = $data;

        $psr7Session
            ->expects(self::any())
            ->method('get')
            ->willReturnCallback(function (string $key, $default = null) use ($psr7Session) {
                if (isset($psr7Session->__data[$key])) {
                    return $psr7Session->__data[$key];
                }

                return $default;
            })
        ;

        $psr7Session
            ->expects(self::any())
            ->method('has')
            ->willReturnCallback(function (string $key) use ($psr7Session) {
                return isset($psr7Session->__data[$key]);
            })
        ;

        $psr7Session
            ->expects(self::any())
            ->method('set')
            ->willReturnCallback(function (string $key, $value) use ($psr7Session) {
                $psr7Session->__data[$key] = $value;
            })
        ;

        $psr7Session
            ->expects(self::any())
            ->method('remove')
            ->willReturnCallback(function (string $key) use ($psr7Session) {
                unset($psr7Session->__data[$key]);
            })
        ;

        return $psr7Session;
    }
}
