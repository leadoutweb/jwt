<?php

namespace Tests\Stubs;

use Illuminate\Contracts\Auth\Authenticatable;

class AuthenticatableStub implements Authenticatable
{
    /**
     * The auth identifier.
     */
    private string $authIdentifier;

    /**
     * Additional claims to use when issuing a token.
     */
    private array $claims;

    /**
     * Instantiate the class.
     */
    public function __construct(string $authIdentifier, array $claims = [])
    {
        $this->authIdentifier = $authIdentifier;

        $this->claims = $claims;
    }

    /**
     * @inheritDoc
     */
    public function getAuthIdentifierName()
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function getAuthIdentifier()
    {
        return $this->authIdentifier;
    }

    /**
     * @inheritDoc
     */
    public function getAuthPassword()
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function getRememberToken()
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function setRememberToken($value)
    {
        //
    }

    /**
     * @inheritDoc
     */
    public function getRememberTokenName()
    {
        //
    }

    /**
     * Get additional claims to use when issuing a token.
     */
    public function getClaims(): array
    {
        return $this->claims;
    }
}