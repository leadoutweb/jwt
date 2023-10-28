<?php

namespace Tests\Entities;

use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\TokenNotDecodedException;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    /** @test */
    function can_get_the_value()
    {
        $this->assertEquals('value', (new Token('value'))->getValue());
    }

    /** @test */
    function can_get_the_claims()
    {
        $this->assertEquals(new Claims(['sub' => 'ABC-123']), (new Token('value', new Claims(['sub' => 'ABC-123'])))->claims());
    }

    /** @test */
    function can_not_get_the_claims_if_the_token_is_not_decoded()
    {
        $this->expectException(TokenNotDecodedException::class);

        (new Token('value'))->claims();
    }
}
