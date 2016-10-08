<?php

namespace Chubbyphp\Session;

use Psr\Http\Message\ServerRequestInterface as Request;
use PSR7Session\Http\SessionMiddleware;
use PSR7Session\Session\SessionInterface as PSR7Session;

final class Session implements SessionInterface
{
    /**
     * @param Request $request
     * @param string  $key
     *
     * @return bool
     */
    public function has(Request $request, string $key): bool
    {
        return $this->getSession($request)->has($key);
    }

    /**
     * @param Request $request
     * @param string  $key
     *
     * @return mixed
     */
    public function get(Request $request, string $key)
    {
        return json_decode($this->getSession($request)->get($key), true);
    }

    /**
     * @param Request $request
     * @param string  $key
     * @param mixed   $value
     */
    public function set(Request $request, string $key, $value)
    {
        $this->getSession($request)->set($key, json_encode($value));
    }

    /**
     * @param Request $request
     * @param string  $key
     */
    public function remove(Request $request, string $key)
    {
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
