<?php

namespace Tests\Entities;

use Leadout\JWT\Entities\Token;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    /** @test */
    function can_get_the_value()
    {
        $this->assertEquals('value', (new Token('value'))->getValue());
    }
}
