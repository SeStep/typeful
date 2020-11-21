<?php declare(strict_types=1);

namespace SeStep\Typeful\DI;

use Nette\DI\Compiler;
use Nette\DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use SeStep\Typeful\Validation\ValidationError;
use SeStep\Typeful\Validation\ValidationException;

class TypefulExtensionTest extends TestCase
{
    public function testRegisterBase()
    {
        self::assertIsString($this->compile(new TypefulExtension()));
    }

    public function testRegisterValidEntity()
    {
        $typefulExtension = new TypefulExtension();
        $typefulExtension->addTypefulModule('test', [
            'entities' => [
                'box' => [
                    'properties' => [
                        'width' => ['type' => 'typeful.number'],
                        'height' => ['type' => 'typeful.number'],
                        'color' => ['type' => 'typeful.text'],
                    ],
                ],
            ],
        ]);

        self::assertIsString($this->compile($typefulExtension));
    }

    public function testRegisterEntityInvalidType()
    {
        $typefulExtension = new TypefulExtension();
        $typefulExtension->addTypefulModule('test', [
            'entities' => [
                'box' => [
                    'properties' => [
                        'width' => ['type' => 'typeful.number'],
                        'height' => ['type' => 'typeful.number'],
                        'color' => ['type' => 'typeful.color'],
                    ],
                ],
            ],
        ]);

        try {
            $this->compile($typefulExtension);
            self::fail("Exception should have occured during compilation");
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            self::assertCount(1, $errors);
            self::assertEquals(ValidationError::INVALID_TYPE, $errors[0]->getErrorType());
            $errorData = $errors[0]->getErrorData();
            unset($errorData['availableTypes']);
            self::assertEquals(['given' => 'typeful.color'], $errorData);
        }
    }

    public function testRegisterEntityInvalidPropertyConfig()
    {

        $typefulExtension = new TypefulExtension();
        $typefulExtension->addTypefulModule('test', [
            'entities' => [
                'answer' => [
                    'properties' => [
                        'value' => [
                            'type' => 'typeful.text',
                        ],
                        'computationTime' => [
                            'type' => 'typeful.number',
                            'options' => [
                                'max' => 'over 9000',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        try {
            $this->compile($typefulExtension);
            self::fail("Exception should have occured during compilation");
        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            self::assertCount(1, $errors);
            self::assertEquals('schema-error', $errors[0]->getErrorType());
            $message = $errors[0]->getErrorData()['messages'][0];
            self::assertStringContainsString('max', $message);
            self::assertStringContainsString('max', $message);
        }
    }

    private function compile(TypefulExtension $extension, array $additionalExtensions = [])
    {
        $compiler = new Compiler(new ContainerBuilder());
        $compiler->addExtension('typeful', $extension);
        foreach ($additionalExtensions as $name => $extension) {
            $compiler->addExtension($name, $extension);
        }

        $containerCode = $compiler->compile();

        $printDir = __DIR__ . '/../../test_output/' . $this->getName() . '.php';
        file_put_contents($printDir, "<?php\n" . $containerCode);

        return $containerCode;
    }
}
