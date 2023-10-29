<?php

namespace Leadout\JWT\Entities;

use Leadout\JWT\Exceptions\TokenNotDecodedException;

class Token
{
    /**
     * The encoded value of the token.
     */
    private string $value;

    /**
     * The claims in the token.
     */
    private ?Claims $claims;

    /**
     * Instantiate the class.
     */
    public function __construct(string $value, Claims $claims = null)
    {
        $this->value = $value;

        $this->claims = $claims;
    }

    /**
     * Get the encoded value of the token.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Get the claims in the token.
     */
    public function claims(): Claims
    {
        if (! $this->claims) {
            throw new TokenNotDecodedException;
        }

        return $this->claims;
    }
}
