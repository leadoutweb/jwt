<?php

namespace Tests\TokenProviders\Drivers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\InvalidTokenException;
use Leadout\JWT\TokenProviders\Contract as TokenProvider;

trait ContractTests
{
    /**
     * The token provider to test.
     */
    protected TokenProvider $tokenProvider;

    /**
     * Set up the test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenProvider = $this->getTokenProvider();
    }

    /** @test */
    public function can_encode_and_decode_a_token()
    {
        $claims = $this->getValidClaims();

        $encoded = $this->tokenProvider->encode($claims);

        $this->assertNotNull($encoded->getValue());

        $this->assertEquals($claims, $this->tokenProvider->decode(new Token($encoded->getValue()))->claims());
    }

    /** @test */
    public function can_not_decode_a_malformed_token()
    {
        $this->expectException(InvalidTokenException::class);

        $this->tokenProvider->decode(new Token('foo'));
    }

    /** @test */
    public function can_not_decode_a_token_with_an_invalid_signature()
    {
        $claims = $this->getValidClaims();

        $encoded = $this->tokenProvider->encode($claims);

        $this->expectException(InvalidTokenException::class);

        $this->tokenProvider->decode(new Token($encoded->getValue().'-foo'));
    }

    /**
     * Get the token provider to test.
     */
    abstract protected function getTokenProvider(): TokenProvider;

    /**
     * Get a valid claims.
     */
    protected function getValidClaims(): Claims
    {
        return new Claims([
            'jti' => Str::uuid()->toString(),
            'sub' => 'ABC-123',
            'iat' => Carbon::now()->timestamp,
            'nbf' => Carbon::now()->timestamp,
            'exp' => Carbon::now()->addHour()->timestamp,
        ]);
    }
}
