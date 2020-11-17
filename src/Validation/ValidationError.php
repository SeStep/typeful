<?php declare(strict_types=1);

namespace SeStep\Typeful\Validation;

class ValidationError
{
    const INVALID_TYPE = 'typeful.error.invalidType';
    const INVALID_VALUE = 'typeful.error.invalidValue';
    const SURPLUS_FIELD = 'typeful.error.surplusField';
    const UNDEFINED_VALUE = 'typeful.error.undefinedValue';

    /** @var string */
    private $errorType;
    /** @var array */
    private $errorData;

    public function __construct(string $errorType, array $errorData = [])
    {
        $this->errorType = $errorType;
        $this->errorData = $errorData;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public static function invalidType(array $expectedTypes, mixed $actualValue): ValidationError
    {
        $expectedTypesArr = [];
        foreach ($expectedTypes as $type) {
            $expectedTypesArr[] = self::getType($type);
        }
        return new ValidationError(self::INVALID_TYPE, [
            'expectedType' => $expectedTypesArr,
            'actualType' => self::getType($actualValue),
        ]);
    }

    private static function getType(mixed $value): string
    {
        if (is_object($actualValue)) {
            $value = get_class($value);
        }

        if (class_exists($value)) {
            $actualType = 'instanceOf(' . get_class($value) . ')';
        } else {
            $actualType = gettype($value);
        }

        return $actualType;
    }
}
