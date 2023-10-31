<?php

namespace Tests;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Validated;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Testing\Fakes\EventFake;
use Leadout\JWT\Blacklists\Drivers\Fake as BlacklistFake;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\JWTGuard;
use Leadout\JWT\TokenManager;
use Leadout\JWT\TokenProviders\Drivers\Fake as TokenProviderFake;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\AuthenticatableStub;

class JWTGuardTest extends TestCase
{
    /**
     * The guard to test.
     */
    protected JWTGuard $guard;

    /**
     * A user provider mock object.
     */
    protected MockObject $provider;

    /**
     * The token manager used for the guard.
     */
    protected TokenManager $manager;

    /**
     * The blacklist used for the manager.
     */
    protected BlacklistFake $blacklist;

    /**
     * The event fake used for the guard.
     */
    protected EventFake $events;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->guard = new JWTGuard(
            'my-guard',
            $this->provider = $this->createMock(UserProvider::class),
            $this->manager = new TokenManager(
                new TokenProviderFake,
                $this->blacklist = new BlacklistFake,
                'my-guard',
                []
            ),
            $this->events = new EventFake(new Dispatcher),
        );
    }

    /** @test */
    public function can_get_the_user()
    {
        $user = new AuthenticatableStub('ABC-123');

        $token = $this->manager->issue($user);

        $this->provider->method('retrieveById')->willReturn($user);

        $this->guard->setRequest($this->getRequest($token));

        $this->assertSame($user, $this->guard->user());
    }

    /** @test */
    public function can_not_get_the_user_if_the_token_is_blacklisted()
    {
        $user = new AuthenticatableStub('ABC-123');

        $token = $this->manager->issue($user);

        $this->blacklist->put($token->claims()->jti(), null);

        $this->guard->setRequest($this->getRequest($token));

        $this->assertNull($this->guard->user());
    }

    /** @test */
    public function can_attempt_to_issue_a_token_successfully()
    {
        $user = new AuthenticatableStub('ABC-123');

        $this->provider->method('retrieveByCredentials')->willReturn($user);

        $this->provider->method('validateCredentials')->willReturn(true);

        $this->assertEquals(
            'ABC-123',
            $this->guard->attempt(['email' => 'john@example.com', 'password' => 'hidden'])->claims()->sub()
        );

        $this->events->assertDispatched(function (Attempting $event) {
            return
                $event->guard == 'my-guard' &&
                $event->credentials == ['email' => 'john@example.com', 'password' => 'hidden'] &&
                ! $event->remember;
        });

        $this->events->assertDispatched(function (Validated $event) use ($user) {
            return $event->guard == 'my-guard' && $event->user == $user;
        });
    }

    /** @test */
    public function can_attempt_to_issue_a_token_when_the_credentials_cannot_be_validated()
    {
        $user = new AuthenticatableStub('ABC-123');

        $this->provider->method('retrieveByCredentials')->willReturn($user);

        $this->provider->method('validateCredentials')->willReturn(false);

        $this->assertNull($this->guard->attempt(['email' => 'john@example.com', 'password' => 'hidden']));

        $this->events->assertDispatched(function (Attempting $event) {
            return
                $event->guard == 'my-guard' &&
                $event->credentials == ['email' => 'john@example.com', 'password' => 'hidden'] &&
                ! $event->remember;
        });

        $this->events->assertDispatched(function (Failed $event) use ($user) {
            return
                $event->guard == 'my-guard' &&
                $event->user == $user &&
                $event->credentials == ['email' => 'john@example.com', 'password' => 'hidden'];
        });
    }

    /** @test */
    public function can_attempt_to_issue_a_token_when_the_credentials_cannot_be_used_for_retrieving()
    {
        $this->provider->method('retrieveByCredentials')->willReturn(null);

        $this->assertNull($this->guard->attempt(['email' => 'john@example.com', 'password' => 'hidden']));

        $this->events->assertDispatched(function (Attempting $event) {
            return
                $event->guard == 'my-guard' &&
                $event->credentials == ['email' => 'john@example.com', 'password' => 'hidden'] &&
                ! $event->remember;
        });

        $this->events->assertDispatched(function (Failed $event) {
            return
                $event->guard == 'my-guard' &&
                $event->user == null &&
                $event->credentials == ['email' => 'john@example.com', 'password' => 'hidden'];
        });
    }

    /** @test */
    public function can_get_the_token_manager()
    {
        $this->assertSame($this->manager, $this->guard->getTokenManager());
    }

    /**
     * Get a request with the given token used as authentication.
     */
    private function getRequest(Token $token): Request
    {
        return new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token->getValue()]);
    }
}
