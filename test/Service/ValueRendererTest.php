<?php declare(strict_types=1);

namespace SeStep\Typeful\Service;

use PHPUnit\Framework\TestCase;
use SeStep\Typeful\Types\NumberType;
use SeStep\Typeful\Types\TextType;

class ValueRendererTest extends TestCase
{
    public function testRenderNoChange()
    {
        $renderer = $this->createTestInstance();
        self::assertEquals('Hello', $renderer->render('Hello', new TextType(), []));
    }

    public function testRenderToLower()
    {
        $renderer = $this->createTestInstance();

        $type = new TextType();
        $options = ['render' => ['to_lower']];
        self::assertEquals('hello', $renderer->render('Hello', $type, $options));
    }


    /**
     * @param float $expectedValue
     * @param float $value
     * @param array $render
     *
     * @testWith [5.5, 2.751, ["double", "typeRender"]]
     *           [5.6, 2.751, ["typeRender", "double"]]
     *           [9, 1.125, ["double", "double", "typeRender", "double"]]
     */
    public function testRenderOrder($expectedValue, $value, array $render)
    {
        $renderer = $this->createTestInstance();

        $type = new NumberType();
        self::assertEquals($expectedValue, $renderer->render($value, $type, ['precision' => 1, 'render' => $render]));
    }


    private function createTestInstance(): ValueRenderer
    {
        $renderer = new ValueRenderer();
        $renderer
            ->addRenderer('to_lower', function ($value) {
                return mb_strtolower($value);
            })
            ->addRenderer('double', function ($value) {
                return $value * 2;
            });

        return $renderer;
    }
}
