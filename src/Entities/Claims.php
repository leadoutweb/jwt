<?php

namespace Leadout\JWT\Entities;

use Leadout\JWT\Exceptions\InvalidClaimException;

class Claims
{
    /**
     * The raw claims.
     */
    private array $value;

    /**
     * Instantiate the class.
     */
    public function __construct(array $value)
    {
        $this->value = $value;
    }

    /**
     * Get the raw claims.
     */
    public function all(): array
    {
        return $this->value;
    }

    /**
     * Get the value of the claim with the given name.
     */
    public function get(string $name): string
    {
        if (! isset($this->value[$name])) {
            throw new InvalidClaimException;
        }

        return $this->value[$name];
    }

    /**
     * Get the jti claim.
     */
    public function jti(): string
    {
        return $this->get('jti');
    }

    /**
     * Get the aud claim.
     */
    public function aud(): string
    {
        return $this->get('aud');
    }

    /**
     * Get the iss claim.
     */
    public function iss(): string
    {
        return $this->get('iss');
    }

    /**
     * Get the iat claim.
     */
    public function iat(): string
    {
        return $this->get('iat');
    }

    /**
     * Get the nbf claim.
     */
    public function nbf(): string
    {
        return $this->get('nbf');
    }

    /**
     * Get the exp claim.
     */
    public function exp(): string
    {
        return $this->get('exp');
    }

    /**
     * Get the sub claim.
     */
    public function sub(): string
    {
        return $this->get('sub');
    }
}
