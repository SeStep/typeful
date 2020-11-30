<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

use PHPUnit\Framework\TestCase;
use SeStep\Typeful\Validation\ValidationError;

class SelectionTypeTest extends TestCase
{
    const TESTING_ITEMS = [
        'board-games' => 'Board Games',
        'card-games' => 'Card Games',
        'video-games' => 'Video Games',
    ];

    public function testGetItemsIterator()
    {
        $type = new SelectionType();
        $items = $type->getItems(['items' => new \ArrayIterator(self::TESTING_ITEMS)]);

        self::assertEquals(self::TESTING_ITEMS, $items);
    }

    public function testGetItemsItemsCallback()
    {
        $type = new SelectionType();
        $items = $type->getItems([
            'items' => function () {
                return self::TESTING_ITEMS;
            },
        ]);

        self::assertEquals(self::TESTING_ITEMS, $items);
    }

    /**
     * @testWith ["board-games", true]
     *           ["video-games", true]
     *           ["mind-games", false]
     *
     * @param string $value
     * @param bool $expectValid
     */
    public function testValidateValue(string $value, bool $expectValid)
    {
        $type = new SelectionType();

        $error = $type->validateValue($value, $this->getTestOptions());
        if ($expectValid) {
            self::assertNull($error);
        } else {
            self::assertInstanceOf(ValidationError::class, $error);
            self::assertEquals(ValidationError::INVALID_VALUE, $error->getErrorType());
        }
    }

    public function testRenderValue()
    {
        $type = new SelectionType();
        $value = $type->renderValue('video-games', $this->getTestOptions());
        self::assertEquals('Video Games', $value);
    }

    public function testEnumerateConstants()
    {
        $items = SelectionType::enumerateConstants(DummyEnumType::class);
        self::assertEquals([
            'AC' => 'ALTERNATING',
            'DC' => 'DIRECT',
        ], $items);
    }

    public function testEnumerateConstantsWithPrefix()
    {
        $items = SelectionType::enumerateConstants(DummyEnumType::class, 'CURRENT_');
        self::assertEquals([
            'AC' => 'CURRENT_ALTERNATING',
            'DC' => 'CURRENT_DIRECT',
        ], $items);
    }

    private function getTestOptions()
    {
        return [
            'items' => self::TESTING_ITEMS,
        ];
    }
}
