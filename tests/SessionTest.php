<?php

namespace Chubbyphp\Tests\Translation;

use Chubbyphp\Session\FlashMessage;
use Chubbyphp\Session\Session;
use Chubbyphp\Session\SessionInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use PSR7Sessions\Storageless\Http\SessionMiddleware;
use PSR7Sessions\Storageless\Session\SessionInterface as PSR7Session;

/**
 * @covers \Chubbyphp\Session\Session
 */
final class SessionTest extends TestCase
{
    public function testHasWithValue()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([
                'some.existing.key' => json_encode(['key' => 'value']),
            ]),
        ]);

        $logger = $this->getLogger();

        $session = new Session($logger);

        self::assertTrue($session->has($request, 'some.existing.key'));

        self::assertCount(1, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('session: has key {key}, exists {exists}', $logger->__logs[0]['message']);
        self::assertSame(['key' => 'some.existing.key', 'exists' => true], $logger->__logs[0]['context']);
    }

    public function testHasWithDefault()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([]),
        ]);

        $logger = $this->getLogger();

        $session = new Session($logger);

        self::assertFalse($session->has($request, 'some.notexisting.key'));

        self::assertCount(1, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('session: has key {key}, exists {exists}', $logger->__logs[0]['message']);
        self::assertSame(['key' => 'some.notexisting.key', 'exists' => false], $logger->__logs[0]['context']);
    }

    public function testGetWithValue()
    {
        $expectedValue = ['key' => 'value'];

        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([
                'some.existing.key' => json_encode($expectedValue),
            ]),
        ]);

        $logger = $this->getLogger();

        $session = new Session($logger);

        self::assertSame($expectedValue, $session->get($request, 'some.existing.key'));

        self::assertCount(1, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('session: get key {key}, exists {exists}', $logger->__logs[0]['message']);
        self::assertSame(['key' => 'some.existing.key', 'exists' => true], $logger->__logs[0]['context']);
    }

    public function testGetWithDefault()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([]),
        ]);

        $logger = $this->getLogger();

        $session = new Session($logger);

        self::assertNull($session->get($request, 'some.notexisting.key'));

        self::assertCount(1, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('session: get key {key}, exists {exists}', $logger->__logs[0]['message']);
        self::assertSame(['key' => 'some.notexisting.key', 'exists' => false], $logger->__logs[0]['context']);
    }

    public function testSet()
    {
        $expectedValue = ['key' => 'value'];

        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([]),
        ]);

        $logger = $this->getLogger();

        $session = new Session($logger);

        self::assertSame(null, $session->get($request, 'some.existing.key'));

        $session->set($request, 'some.existing.key', $expectedValue);

        self::assertSame($expectedValue, $session->get($request, 'some.existing.key'));

        self::assertCount(3, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('session: get key {key}, exists {exists}', $logger->__logs[0]['message']);
        self::assertSame(['key' => 'some.existing.key', 'exists' => false], $logger->__logs[0]['context']);
        self::assertSame('info', $logger->__logs[1]['level']);
        self::assertSame('session: set key {key}', $logger->__logs[1]['message']);
        self::assertSame(['key' => 'some.existing.key'], $logger->__logs[1]['context']);
        self::assertSame('info', $logger->__logs[2]['level']);
        self::assertSame('session: get key {key}, exists {exists}', $logger->__logs[2]['message']);
        self::assertSame(['key' => 'some.existing.key', 'exists' => true], $logger->__logs[2]['context']);
    }

    public function testRemove()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([
                'some.existing.key' => json_encode(['key' => 'value']),
            ]),
        ]);

        $logger = $this->getLogger();

        $session = new Session($logger);

        self::assertTrue($session->has($request, 'some.existing.key'));

        $session->remove($request, 'some.existing.key');

        self::assertFalse($session->has($request, 'some.existing.key'));

        self::assertCount(3, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('session: has key {key}, exists {exists}', $logger->__logs[0]['message']);
        self::assertSame(['key' => 'some.existing.key', 'exists' => true], $logger->__logs[0]['context']);
        self::assertSame('info', $logger->__logs[1]['level']);
        self::assertSame('session: remove key {key}', $logger->__logs[1]['message']);
        self::assertSame(['key' => 'some.existing.key'], $logger->__logs[1]['context']);
        self::assertSame('info', $logger->__logs[2]['level']);
        self::assertSame('session: has key {key}, exists {exists}', $logger->__logs[2]['message']);
        self::assertSame(['key' => 'some.existing.key', 'exists' => false], $logger->__logs[2]['context']);
    }

    public function testAddFlash()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([]),
        ]);

        $flashMessage = new FlashMessage(FlashMessage::TYPE_SUCCESS, 'Worked like a charm');

        $logger = $this->getLogger();

        $session = new Session($logger);

        self::assertFalse($session->has($request, Session::FLASH_KEY));

        $session->addFlash($request, $flashMessage);

        self::assertTrue($session->has($request, Session::FLASH_KEY));

        self::assertCount(3, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('session: has key {key}, exists {exists}', $logger->__logs[0]['message']);
        self::assertSame(['key' => SessionInterface::FLASH_KEY, 'exists' => false], $logger->__logs[0]['context']);
        self::assertSame('info', $logger->__logs[1]['level']);
        self::assertSame('session: set key {key}', $logger->__logs[1]['message']);
        self::assertSame(['key' => SessionInterface::FLASH_KEY], $logger->__logs[1]['context']);
        self::assertSame('info', $logger->__logs[2]['level']);
        self::assertSame('session: has key {key}, exists {exists}', $logger->__logs[2]['message']);
        self::assertSame(['key' => SessionInterface::FLASH_KEY, 'exists' => true], $logger->__logs[2]['context']);
    }

    public function testGetFlash()
    {
        $expectedFlashMessage = new FlashMessage(FlashMessage::TYPE_SUCCESS, 'Worked like a charm');

        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([
                Session::FLASH_KEY => json_encode($expectedFlashMessage),
            ]),
        ]);

        $logger = $this->getLogger();

        $session = new Session($logger);

        self::assertTrue($session->has($request, Session::FLASH_KEY));

        $flashMessage = $session->getFlash($request);

        self::assertSame($expectedFlashMessage->jsonSerialize(), $flashMessage->jsonSerialize());

        self::assertFalse($session->has($request, Session::FLASH_KEY));

        self::assertCount(5, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('session: has key {key}, exists {exists}', $logger->__logs[0]['message']);
        self::assertSame(['key' => SessionInterface::FLASH_KEY, 'exists' => true], $logger->__logs[0]['context']);
        self::assertSame('info', $logger->__logs[1]['level']);
        self::assertSame('session: has key {key}, exists {exists}', $logger->__logs[1]['message']);
        self::assertSame(['key' => SessionInterface::FLASH_KEY, 'exists' => true], $logger->__logs[1]['context']);
        self::assertSame('info', $logger->__logs[2]['level']);
        self::assertSame('session: get key {key}, exists {exists}', $logger->__logs[2]['message']);
        self::assertSame(['key' => SessionInterface::FLASH_KEY, 'exists' => true], $logger->__logs[2]['context']);
        self::assertSame('info', $logger->__logs[3]['level']);
        self::assertSame('session: remove key {key}', $logger->__logs[3]['message']);
        self::assertSame(['key' => SessionInterface::FLASH_KEY], $logger->__logs[3]['context']);
        self::assertSame('info', $logger->__logs[4]['level']);
        self::assertSame('session: has key {key}, exists {exists}', $logger->__logs[4]['message']);
        self::assertSame(['key' => SessionInterface::FLASH_KEY, 'exists' => false], $logger->__logs[4]['context']);
    }

    public function testGetNullFlash()
    {
        $request = $this->getRequest([
            SessionMiddleware::SESSION_ATTRIBUTE => $this->getPSR7Session([]),
        ]);

        $logger = $this->getLogger();

        $session = new Session($logger);

        self::assertNull($session->getFlash($request));

        self::assertCount(1, $logger->__logs);
        self::assertSame('info', $logger->__logs[0]['level']);
        self::assertSame('session: has key {key}, exists {exists}', $logger->__logs[0]['message']);
        self::assertSame(['key' => SessionInterface::FLASH_KEY, 'exists' => false], $logger->__logs[0]['context']);
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

    /**
     * @return LoggerInterface
     */
    private function getLogger(): LoggerInterface
    {
        $methods = [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug',
        ];

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
        $logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->setMethods(array_merge($methods, ['log']))
            ->getMockForAbstractClass()
        ;

        $logger->__logs = [];

        foreach ($methods as $method) {
            $logger
                ->expects(self::any())
                ->method($method)
                ->willReturnCallback(
                    function (string $message, array $context = []) use ($logger, $method) {
                        $logger->log($method, $message, $context);
                    }
                )
            ;
        }

        $logger
            ->expects(self::any())
            ->method('log')
            ->willReturnCallback(
                function (string $level, string $message, array $context = []) use ($logger) {
                    $logger->__logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
                }
            )
        ;

        return $logger;
    }
}
