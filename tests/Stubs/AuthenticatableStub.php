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
     * Instantiate the class.
     */
    public function __construct(string $authIdentifier)
    {
        $this->authIdentifier = $authIdentifier;
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
}