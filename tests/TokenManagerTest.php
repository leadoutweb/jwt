<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Leadout\JWT\Blacklists\Drivers\Fake as BlacklistFake;
use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\InvalidAudienceException;
use Leadout\JWT\Exceptions\TokenBlacklistedException;
use Leadout\JWT\Exceptions\TokenExpiredException;
use Leadout\JWT\TokenManager;
use Leadout\JWT\TokenProviders\Drivers\Fake as TokenProviderFake;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\AuthenticatableStub;

class TokenManagerTest extends TestCase
{
    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        Str::createUuidsNormally();

        parent::tearDown();
    }

    /** @test */
    function the_jti_is_set_to_a_uuid()
    {
        Str::createUuidsUsing(fn() => Str::of('1-2-3-4'));

        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals('1-2-3-4', $tokenManager->claims($this->getRequest($token))->jti());
    }

    /** @test */
    function the_iat_is_set_to_the_current_time()
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals(
            Carbon::parse('2023-01-01 12:00:00')->timestamp,
            $tokenManager->claims($this->getRequest($token))->iat()
        );
    }

    /** @test */
    function the_nbf_is_set_to_the_current_time()
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals(
            Carbon::parse('2023-01-01 12:00:00')->timestamp,
            $tokenManager->claims($this->getRequest($token))->nbf()
        );
    }

    /** @test */
    function the_sub_is_set_to_the_user_auth_identifier()
    {
        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals('ABC-123', $tokenManager->claims($this->getRequest($token))->sub());
    }

    /** @test */
    function the_ttl_from_the_config_is_used_for_token_expiration()
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $tokenManager = $this->getTokenManager(config: ['ttl' => 120]);

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals(
            Carbon::parse('2023-01-01 14:00:00')->timestamp,
            $tokenManager->claims($this->getRequest($token))->exp()
        );
    }

    /** @test */
    function can_add_additional_claims_from_configuration_to_tokens()
    {
        $tokenManager = $this->getTokenManager(config: ['claims' => ['key' => 'value']]);

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals('value', $tokenManager->claims($this->getRequest($token))->all()['key']);
    }

    /** @test */
    function can_add_additional_claims_from_the_authenticatable_to_tokens()
    {
        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123', ['key' => 'value']));

        $this->assertEquals('value', $tokenManager->claims($this->getRequest($token))->all()['key']);
    }

    /** @test */
    function can_get_the_claims_of_a_token()
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $claims = new Claims([
            'jti' => '1-2-3-4',
            'aud' => 'jwt',
            'exp' => Carbon::parse('2023-01-01 12:00:00')->timestamp,
            'sub' => 'ABC-123'
        ]);

        $tokenProvider = new TokenProviderFake;

        $token = $tokenProvider->encode($claims);

        $this->assertEquals(
            $claims,
            $this->getTokenManager(tokenProvider: $tokenProvider)->claims($this->getRequest($token))
        );
    }

    /** @test */
    function can_not_get_the_claims_of_a_token_that_has_been_blacklisted()
    {
        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $tokenManager->invalidate($this->getRequest($token));

        $this->expectException(TokenBlacklistedException::class);

        $tokenManager->claims($this->getRequest($token));
    }

    /** @test */
    function can_not_get_the_claims_of_a_token_that_has_expired()
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $tokenManager = $this->getTokenManager(config: ['ttl' => 60]);

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        Carbon::setTestNow('2023-01-01 13:00:01');

        $this->expectException(TokenExpiredException::class);

        $tokenManager->claims($this->getRequest($token));
    }

    /** @test */
    function can_not_get_the_claims_of_a_token_with_another_aud()
    {
        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->expectException(InvalidAudienceException::class);

        $this->getTokenManager(name: 'other')->claims($this->getRequest($token));
    }

    /** @test */
    function can_get_the_token_provider()
    {
        $tokenManager = $this->getTokenManager(tokenProvider: $tokenProvider = new TokenProviderFake);

        $this->assertSame($tokenProvider, $tokenManager->getTokenProvider());
    }

    /**
     * Get a token manager to use for testing.
     */
    private function getTokenManager(
        TokenProviderFake $tokenProvider = null,
        BlacklistFake $blacklist = null,
        string $name = 'jwt',
        array $config = []
    ): TokenManager {
        return new TokenManager(
            $tokenProvider ?: new TokenProviderFake,
            $blacklist ?: new BlacklistFake,
            $name,
            $config
        );
    }

    /**
     * Get a request with the given token used as authentication.
     */
    private function getRequest(Token $token): Request
    {
        return new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer '.$token->getValue()]);
    }
}
