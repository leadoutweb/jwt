<?php

namespace Leadout\JWT;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\JWTException;

class JWTGuard implements Guard
{
    use GuardHelpers;

    /**
     * The HTTP request.
     */
    private Request $request;

    /**
     * The token manager.
     */
    private TokenManager $tokenManager;

    /**
     * The user provider.
     */
    private UserProvider $userProvider;

    /**
     * Instantiate the class.
     */
    public function __construct(Request $request, TokenManager $tokenManager, UserProvider $userProvider)
    {
        $this->request = $request;

        $this->tokenManager = $tokenManager;

        $this->userProvider = $userProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function user()
    {
        if ($this->user) {
            return $this->user;
        }

        try {
            return $this->user = $this->userProvider->retrieveById(
                $this->tokenManager->decode($this->request)->claims()->sub()
            );
        } catch (JWTException) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validate(array $credentials = [])
    {
        return $this->userProvider->retrieveByCredentials($credentials) != null;
    }

    /**
     * Set the current request instance.
     */
    public function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Issue a token for the given user.
     */
    public function issue(Authenticatable $user): Token
    {
        return $this->tokenManager->issue($user);
    }

    /**
     * Refresh the token.
     */
    public function refresh(): Token
    {
        return $this->tokenManager->refresh($this->request);
    }

    /**
     * Get the token manager.
     */
    public function getTokenManager(): TokenManager
    {
        return $this->tokenManager;
    }
}
