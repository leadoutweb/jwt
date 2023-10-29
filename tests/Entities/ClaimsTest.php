<?php

namespace Tests\Entities;

use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Exceptions\InvalidClaimException;
use PHPUnit\Framework\TestCase;

class ClaimsTest extends TestCase
{
    /** @test */
    public function can_get_all_claims()
    {
        $this->assertEquals(['sub' => 'ABC-123'], (new Claims(['sub' => 'ABC-123']))->all());
    }

    /** @test */
    public function can_get_the_value_of_a_claim()
    {
        $this->assertEquals('ABC-123', (new Claims(['sub' => 'ABC-123']))->get('sub'));
    }

    /** @test */
    public function can_not_get_the_value_of_a_claim_that_is_not_set()
    {
        $this->expectException(InvalidClaimException::class);

        (new Claims([]))->get('sub');
    }

    /** @test */
    public function can_get_the_jti_claim()
    {
        $this->assertEquals('value', (new Claims(['jti' => 'value']))->jti());
    }

    /** @test */
    public function can_get_the_aud_claim()
    {
        $this->assertEquals('value', (new Claims(['aud' => 'value']))->aud());
    }

    /** @test */
    public function can_get_the_iss_claim()
    {
        $this->assertEquals('value', (new Claims(['iss' => 'value']))->iss());
    }

    /** @test */
    public function can_get_the_iat_claim()
    {
        $this->assertEquals('value', (new Claims(['iat' => 'value']))->iat());
    }

    /** @test */
    public function can_get_the_nbf_claim()
    {
        $this->assertEquals('value', (new Claims(['nbf' => 'value']))->nbf());
    }

    /** @test */
    public function can_get_the_exp_claim()
    {
        $this->assertEquals('value', (new Claims(['exp' => 'value']))->exp());
    }

    /** @test */
    public function can_get_the_sub_claim()
    {
        $this->assertEquals('value', (new Claims(['sub' => 'value']))->sub());
    }
}
