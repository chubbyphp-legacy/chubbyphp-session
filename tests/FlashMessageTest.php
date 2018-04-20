<?php

namespace Chubbyphp\Tests\Translation;

use Chubbyphp\Session\FlashMessage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Chubbyphp\Session\FlashMessage
 */
final class FlashMessageTest extends TestCase
{
    /**
     * @dataProvider getMessagesProvider
     *
     * @param string $expectedType
     * @param string $expectedLongType
     * @param string $expectedMessage
     */
    public function testAll(string $expectedType, string $expectedLongType, string $expectedMessage)
    {
        $flashMessage = new FlashMessage($expectedType, $expectedMessage);

        self::assertSame($expectedType, $flashMessage->getType());
        self::assertSame($expectedLongType, $flashMessage->getLongType());
        self::assertSame($expectedMessage, $flashMessage->getMessage());
        self::assertSame(
            [
                FlashMessage::JSON_KEY_TYPE => $expectedType,
                FlashMessage::JSON_KEY_MESSAGE => $expectedMessage,
            ],
            $flashMessage->jsonSerialize()
        );
    }

    /**
     * @return array
     */
    public function getMessagesProvider(): array
    {
        return [
            [
                'expectedType' => FlashMessage::TYPE_PRIMARY,
                'expectedLongType' => 'primary',
                'expectedMessage' => 'Message 1',
            ],
            [
                'expectedType' => FlashMessage::TYPE_SUCCESS,
                'expectedLongType' => 'success',
                'expectedMessage' => 'Message 2',
            ],
            [
                'expectedType' => FlashMessage::TYPE_INFO,
                'expectedLongType' => 'info',
                'expectedMessage' => 'Message 3',
            ],
            [
                'expectedType' => FlashMessage::TYPE_WARNING,
                'expectedLongType' => 'warning',
                'expectedMessage' => 'Message 4',
            ],
            [
                'expectedType' => FlashMessage::TYPE_DANGER,
                'expectedLongType' => 'danger',
                'expectedMessage' => 'Message 5',
            ],
            [
                'expectedType' => 'u',
                'expectedLongType' => 'info',
                'expectedMessage' => 'Message 5',
            ],
        ];
    }
}
