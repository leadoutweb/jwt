<?php

namespace Leadout\JWT\TokenProviders;

use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;

interface Contract
{
    /**
     * Encode the given claims into token.
     */
    public function encode(Claims $claims): Token;

    /**
     * Decode the given token.
     */
    public function decode(Token $token): Token;
}
