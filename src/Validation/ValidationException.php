<?php declare(strict_types=1);

namespace SeStep\Typeful\Validation;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /** @var ValidationError[] */
    private $errors;

    /**
     * @param string $message
     * @param ValidationError[] $errors
     */
    public function __construct(string $message, ...$errors)
    {
        parent::__construct($message . "\n" . var_export($errors, true));
        $this->errors = $errors;
    }

    /** @return ValidationError[] */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
