<?php

namespace Leadout\JWT\Entities;

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
     * Get the jti claim.
     */
    public function jti(): string
    {
        return $this->value['jti'];
    }

    /**
     * Get the aud claim.
     */
    public function aud(): string
    {
        return $this->value['aud'];
    }

    /**
     * Get the iss claim.
     */
    public function iss(): string
    {
        return $this->value['iss'];
    }

    /**
     * Get the iat claim.
     */
    public function iat(): string
    {
        return $this->value['iat'];
    }

    /**
     * Get the nbf claim.
     */
    public function nbf(): string
    {
        return $this->value['nbf'];
    }

    /**
     * Get the exp claim.
     */
    public function exp(): string
    {
        return $this->value['exp'];
    }

    /**
     * Get the sub claim.
     */
    public function sub(): string
    {
        return $this->value['sub'];
    }
}
