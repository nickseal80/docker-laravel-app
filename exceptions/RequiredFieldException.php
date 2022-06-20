<?php

namespace exceptions;

use Exception;
use Throwable;

class RequiredFieldException extends Exception
{
    protected string $field;

    public function __construct(string $message = "", int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField(string $field): void
    {
        $this->field = $field;
    }

}
