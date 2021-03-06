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

    public function getErrorData(): array
    {
        return $this->errorData;
    }

    public static function invalidType(array $expectedTypes, $actualValue): ValidationError
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

    private static function getType($value): string
    {
        if (is_object($value)) {
            $value = get_class($value);
        }

        if (class_exists($value)) {
            $actualType = 'instanceOf(' . $value . ')';
        } else {
            $actualType = gettype($value);
        }

        return $actualType;
    }
}
