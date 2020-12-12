<?php declare(strict_types=1);

namespace SeStep\Typeful\Types;

use PHPUnit\Framework\TestCase;

class NumberTypeTest extends TestCase
{
    public function testRenderNoOptions()
    {
        $type = new NumberType();
        self::assertEquals(42, $type->renderValue(42.42));
    }

    /**
     * @param $expectedValue
     * @param $precision
     *
     * @testWith [42, 0]
     *           [42.4, 1]
     *           [42.42, 2]
     *           [40, -1]
     */
    public function testRenderPrecision($expectedValue, $precision)
    {
        $type = new NumberType();
        self::assertEquals($expectedValue, $type->renderValue(42.42, ['precision' => $precision]));
    }
}
