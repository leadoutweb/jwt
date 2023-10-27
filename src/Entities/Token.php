<?php

namespace Leadout\JWT\Entities;

class Token
{
    /**
     * The value of the token.
     */
    private string $value;

    /**
     * Instantiate the class.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Get the value of the token.
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
