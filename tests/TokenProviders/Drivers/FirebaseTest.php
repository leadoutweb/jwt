<?php

namespace Tests\TokenProviders\Drivers;

use Carbon\Carbon;
use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\TokenExpiredException;
use Leadout\JWT\TokenProviders\Contract as TokenProvider;
use Leadout\JWT\TokenProviders\Drivers\Firebase;
use PHPUnit\Framework\TestCase;

class FirebaseTest extends TestCase
{
    use ContractTests;

    /**
     * {@inheritDoc}
     */
    protected function getTokenProvider(): TokenProvider
    {
        return new Firebase([
            'private_key' => dirname(__FILE__).'/../../../resources/jwt.key',
            'public_key' => dirname(__FILE__).'/../../../resources/jwt.key.pub',
        ]);
    }

    /** @test */
    public function can_be_configured_with_a_key()
    {
        $tokenProvider = new Firebase([
            'key' => 'ABC-123',
        ]);

        $claims = $this->getValidClaims();

        $this->assertEquals(
            $claims,
            $tokenProvider->decode(new Token($tokenProvider->encode($claims)->getValue()))->claims()
        );
    }

    /** @test */
    public function it_throws_an_exception_when_decoding_an_expired_token()
    {
        $claims = new Claims([
            ...$this->getValidClaims()->all(),
            'exp' => Carbon::now()->subSecond()->timestamp,
        ]);

        $this->expectException(TokenExpiredException::class);

        $this->tokenProvider->decode($this->tokenProvider->encode($claims));
    }
}
