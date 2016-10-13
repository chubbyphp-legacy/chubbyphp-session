<?php

declare(strict_types=1);

namespace Chubbyphp\Session;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use PSR7Session\Http\SessionMiddleware;
use PSR7Session\Session\SessionInterface as PSR7Session;

final class Session implements SessionInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param Request $request
     * @param string  $key
     *
     * @return bool
     */
    public function has(Request $request, string $key): bool
    {
        $exists = $this->getSession($request)->has($key);

        $this->logger->info('session: has key {key}, exists {exists}', ['key' => $key, 'exists' => $exists]);

        return $exists;
    }

    /**
     * @param Request $request
     * @param string  $key
     *
     * @return mixed
     */
    public function get(Request $request, string $key)
    {
        $jsonValue = $this->getSession($request)->get($key);

        $this->logger->info(
            'session: get key {key}, json value {jsonValue}',
            ['key' => $key, 'jsonValue' => $jsonValue]
        );

        return json_decode((string) $jsonValue, true);
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param mixed   $value
     */
    public function set(Request $request, string $key, $value)
    {
        $jsonValue = json_encode($value);

        $this->logger->info(
            'session: set key {key}, json value {jsonValue}',
            ['key' => $key, 'jsonValue' => $jsonValue]
        );

        $this->getSession($request)->set($key, $jsonValue);
    }

    /**
     * @param Request $request
     * @param string  $key
     */
    public function remove(Request $request, string $key)
    {
        $this->logger->info('session: remove key {key}', ['key' => $key]);

        $this->getSession($request)->remove($key);
    }

    /**
     * @param Request      $request
     * @param FlashMessage $flashMessage
     */
    public function addFlash(Request $request, FlashMessage $flashMessage)
    {
        $this->set($request, self::FLASH_KEY, $flashMessage);
    }

    /**
     * @param Request $request
     *
     * @return FlashMessage|null
     */
    public function getFlash(Request $request)
    {
        if (!$this->has($request, self::FLASH_KEY)) {
            return null;
        }

        $data = $this->get($request, self::FLASH_KEY);
        $this->remove($request, self::FLASH_KEY);

        return new FlashMessage($data[FlashMessage::JSON_KEY_TYPE], $data[FlashMessage::JSON_KEY_MESSAGE]);
    }

    /**
     * @param Request $request
     *
     * @return PSR7Session
     */
    private function getSession(Request $request): PSR7Session
    {
        return $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
    }
}
