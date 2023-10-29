<?php

namespace Tests;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Leadout\JWT\Blacklists\Drivers\Fake as BlacklistFake;
use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\InvalidAudienceException;
use Leadout\JWT\Exceptions\TokenExpiredException;
use Leadout\JWT\Exceptions\TokenInvalidatedException;
use Leadout\JWT\Exceptions\TokenNotProvidedException;
use Leadout\JWT\TokenManager;
use Leadout\JWT\TokenProviders\Drivers\Fake as TokenProviderFake;
use PHPUnit\Framework\TestCase;
use Tests\Stubs\AuthenticatableStub;

class TokenManagerTest extends TestCase
{
    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        Str::createUuidsNormally();

        Carbon::setTestNow();

        parent::tearDown();
    }

    /** @test */
    public function the_jti_is_set_to_a_uuid()
    {
        Str::createUuidsUsing(fn () => Str::of('1-2-3-4'));

        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals('1-2-3-4', $tokenManager->decode($this->getRequest($token))->claims()->jti());
    }

    /** @test */
    public function the_iat_is_set_to_the_current_time()
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals(
            Carbon::parse('2023-01-01 12:00:00')->timestamp,
            $tokenManager->decode($this->getRequest($token))->claims()->iat()
        );
    }

    /** @test */
    public function the_nbf_is_set_to_the_current_time()
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals(
            Carbon::parse('2023-01-01 12:00:00')->timestamp,
            $tokenManager->decode($this->getRequest($token))->claims()->nbf()
        );
    }

    /** @test */
    public function the_sub_is_set_to_the_user_auth_identifier()
    {
        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals('ABC-123', $tokenManager->decode($this->getRequest($token))->claims()->sub());
    }

    /** @test */
    public function the_ttl_from_the_config_is_used_for_token_expiration()
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $tokenManager = $this->getTokenManager(config: ['ttl' => 120]);

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals(
            Carbon::parse('2023-01-01 14:00:00')->timestamp,
            $tokenManager->decode($this->getRequest($token))->claims()->exp()
        );
    }

    /** @test */
    public function can_add_additional_claims_from_configuration_to_tokens()
    {
        $tokenManager = $this->getTokenManager(config: ['claims' => ['key' => 'value']]);

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->assertEquals('value', $tokenManager->decode($this->getRequest($token))->claims()->get('key'));
    }

    /** @test */
    public function can_add_additional_claims_from_the_authenticatable_to_tokens()
    {
        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123', ['key' => 'value']));

        $this->assertEquals('value', $tokenManager->decode($this->getRequest($token))->claims()->get('key'));
    }

    /** @test */
    public function can_decode_a_token()
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $tokenProvider = new TokenProviderFake;

        $token = $tokenProvider->encode(
            new Claims([
                'jti' => '1-2-3-4',
                'aud' => 'jwt',
                'exp' => Carbon::parse('2023-01-01 12:00:00')->timestamp,
                'sub' => 'ABC-123',
            ])
        );

        $this->assertEquals(
            $token,
            $this->getTokenManager(tokenProvider: $tokenProvider)->decode($this->getRequest($token))
        );
    }

    /** @test */
    public function can_not_decode_a_token_that_has_been_invalidated()
    {
        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $tokenManager->invalidate($this->getRequest($token));

        $this->expectException(TokenInvalidatedException::class);

        $tokenManager->decode($this->getRequest($token));
    }

    /** @test */
    public function can_not_decode_a_token_that_has_expired()
    {
        Carbon::setTestNow('2023-01-01 12:00:00');

        $tokenManager = $this->getTokenManager(config: ['ttl' => 60]);

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        Carbon::setTestNow('2023-01-01 13:00:01');

        $this->expectException(TokenExpiredException::class);

        $tokenManager->decode($this->getRequest($token));
    }

    /** @test */
    public function can_not_decode_a_token_with_another_aud()
    {
        $tokenManager = $this->getTokenManager();

        $token = $tokenManager->issue(new AuthenticatableStub('ABC-123'));

        $this->expectException(InvalidAudienceException::class);

        $this->getTokenManager(name: 'other')->decode($this->getRequest($token));
    }

    /** @test */
    public function can_not_decode_a_token_that_is_not_provided()
    {
        $this->expectException(TokenNotProvidedException::class);

        $this->getTokenManager(name: 'other')->decode(new Request);
    }

    /** @test */
    public function can_refresh_a_token()
    {
        Carbon::setTestNow('2023-01-01 11:55:00');

        Str::createUuidsUsing(fn () => Str::of('1-2-3-4'));

        $tokenProvider = new TokenProviderFake;

        $token = $tokenProvider->encode(new Claims([
            'aud' => 'jwt',
            'jti' => 'ABC-123',
            'exp' => Carbon::parse('2023-01-01 12:00:00')->timestamp,
            'key' => 'value',
        ]));

        $tokenManager = $this->getTokenManager(tokenProvider: $tokenProvider);

        $expected = new Claims([
            'aud' => 'jwt',
            'jti' => '1-2-3-4',
            'iat' => Carbon::parse('2023-01-01 11:55:00')->timestamp,
            'nbf' => Carbon::parse('2023-01-01 11:55:00')->timestamp,
            'exp' => Carbon::parse('2023-01-01 12:55:00')->timestamp,
            'key' => 'value',
        ]);

        $this->assertEquals($expected, $tokenManager->refresh($this->getRequest($token))->claims());
    }

    /** @test */
    public function can_not_refresh_a_token_that_has_expired()
    {
        Carbon::setTestNow('2023-01-01 11:55:00');

        $tokenProvider = new TokenProviderFake;

        $token = $tokenProvider->encode(new Claims([
            'aud' => 'jwt',
            'jti' => 'ABC-123',
            'exp' => Carbon::parse('2023-01-01 11:00:00')->timestamp,
        ]));

        $tokenManager = $this->getTokenManager(tokenProvider: $tokenProvider);

        $this->expectException(TokenExpiredException::class);

        $tokenManager->refresh($this->getRequest($token));
    }

    /** @test */
    public function can_not_refresh_a_token_that_has_been_invalidated()
    {
        Carbon::setTestNow('2023-01-01 11:55:00');

        $tokenProvider = new TokenProviderFake;

        $token = $tokenProvider->encode(new Claims([
            'aud' => 'jwt',
            'jti' => 'ABC-123',
            'exp' => Carbon::parse('2023-01-01 12:00:00')->timestamp,
        ]));

        $tokenManager = $this->getTokenManager(tokenProvider: $tokenProvider);

        $tokenManager->invalidate($this->getRequest($token));

        $this->expectException(TokenInvalidatedException::class);

        $tokenManager->decode($this->getRequest($token));
    }

    /** @test */
    public function can_not_refresh_a_token_that_is_not_provided()
    {
        $this->expectException(TokenNotProvidedException::class);

        $this->getTokenManager(name: 'other')->refresh(new Request);
    }

    /** @test */
    public function can_get_the_token_provider()
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
