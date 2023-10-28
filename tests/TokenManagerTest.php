<?php

namespace Tests;

use Illuminate\Http\Request;
use Leadout\JWT\Blacklists\Drivers\Fake as BlacklistFake;
use Leadout\JWT\Exceptions\InvalidAudienceException;
use Leadout\JWT\TokenManager;
use Leadout\JWT\TokenProviders\Drivers\Fake as TokenProviderFake;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\AuthenticatableStub;

class TokenManagerTest extends TestCase
{
    /** @test */
    function can_not_get_the_claims_of_a_token_with_another_aud()
    {
        $tokenManager = new TokenManager(new TokenProviderFake, new BlacklistFake, 'users', []);

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->expectException(InvalidAudienceException::class);

        (new TokenManager(new TokenProviderFake, new BlacklistFake, 'other', []))->claims(
            new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token->getValue()])
        );
    }
}
