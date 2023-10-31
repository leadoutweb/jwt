<?php

namespace Leadout\JWT;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Validated;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Timebox;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\JWTException;

class JWTGuard implements Guard
{
    use GuardHelpers;

    /**
     * The name of the guard.
     */
    protected string $name;

    /**
     * The token manager.
     */
    protected TokenManager $tokenManager;

    /**
     * The event dispatcher.
     */
    protected Dispatcher $events;

    /**
     * The HTTP request.
     */
    protected ?Request $request;

    /**
     * The timebox.
     */
    protected Timebox $timebox;

    /**
     * Instantiate the class.
     */
    public function __construct(
        string $name,
        UserProvider $provider,
        TokenManager $tokenManager,
        Dispatcher $events,
        Request $request = null,
        Timebox $timebox = null
    ) {
        $this->name = $name;

        $this->provider = $provider;

        $this->tokenManager = $tokenManager;

        $this->events = $events;

        $this->request = $request;

        $this->timebox = $timebox ?: new Timebox;
    }

    /**
     * {@inheritDoc}
     */
    public function user(): ?Authenticatable
    {
        if ($this->user) {
            return $this->user;
        }

        try {
            return $this->user = $this->provider->retrieveById(
                $this->tokenManager->decode($this->request)->claims()->sub()
            );
        } catch (JWTException) {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validate(array $credentials = []): bool
    {
        return $this->provider->retrieveByCredentials($credentials) != null;
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
     * Attempt to issue a token for the given credentials.
     */
    public function attempt(array $credentials = []): ?Token
    {
        $this->fireAttemptEvent($credentials);

        $user = $this->provider->retrieveByCredentials($credentials);

        // If an implementation of UserInterface was returned, we'll ask the provider
        // to validate the user against the given credentials, and if they are in
        // fact valid we'll log the users into the application and return true.
        if ($this->hasValidCredentials($user, $credentials)) {
            return $this->issue($user);
        }

        // If the authentication attempt fails we will fire an event so that the user
        // may be notified of any suspicious attempts to access their account from
        // an unrecognized user. A developer may listen to this event as needed.
        $this->fireFailedEvent($user, $credentials);

        return null;
    }

    /**
     * Determine if the user matches the credentials.
     */
    protected function hasValidCredentials(?Authenticatable $user, array $credentials): bool
    {
        return $this->timebox->call(function ($timebox) use ($user, $credentials) {
            $validated = ! is_null($user) && $this->provider->validateCredentials($user, $credentials);

            if ($validated) {
                $timebox->returnEarly();

                $this->fireValidatedEvent($user);
            }

            return $validated;
        }, 200 * 1000);
    }

    /**
     * Fire the attempt event with the arguments.
     */
    protected function fireAttemptEvent(array $credentials): void
    {
        $this->events->dispatch(new Attempting($this->name, $credentials, false));
    }

    /**
     * Fires the validated event if the dispatcher is set.
     */
    protected function fireValidatedEvent(Authenticatable $user): void
    {
        $this->events->dispatch(new Validated($this->name, $user));
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     */
    protected function fireFailedEvent(?Authenticatable $user, array $credentials): void
    {
        $this->events->dispatch(new Failed($this->name, $user, $credentials));
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
