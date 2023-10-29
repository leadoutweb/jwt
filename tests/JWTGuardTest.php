<?php

namespace Tests;

use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Leadout\JWT\Blacklists\Drivers\Fake as BlacklistFake;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\JWTGuard;
use Leadout\JWT\TokenManager;
use Leadout\JWT\TokenProviders\Drivers\Fake as TokenProviderFake;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\AuthenticatableStub;

class JWTGuardTest extends TestCase
{
    /** @test */
    public function can_get_the_user()
    {
        $manager = new TokenManager(new TokenProviderFake, new BlacklistFake, 'my-guard', []);

        $user = new AuthenticatableStub('ABC-123');

        $token = $manager->issue($user);

        $userProvider = $this->createMock(UserProvider::class);

        $userProvider->method('retrieveById')->willReturn($user);

        $guard = new JWTGuard($this->getRequest($token), $manager, $userProvider);

        $this->assertSame($user, $guard->user());
    }

    /** @test */
    public function can_not_get_the_user_if_the_token_is_blacklisted()
    {
        $manager = new TokenManager(new TokenProviderFake, $blacklist = new BlacklistFake, 'my-guard', []);

        $user = new AuthenticatableStub('ABC-123');

        $token = $manager->issue($user);

        $blacklist->put($token->claims()->jti(), null);

        $guard = new JWTGuard($this->getRequest($token), $manager, $this->createMock(UserProvider::class));

        $this->assertNull($guard->user());
    }

    /**
     * Get a request with the given token used as authentication.
     */
    private function getRequest(Token $token): Request
    {
        return new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token->getValue()]);
    }
}
