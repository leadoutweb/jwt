<?php

namespace Leadout\JWT\TokenProviders;

use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;

interface Contract
{
    /**
     * Encode the given claims in a token.
     */
    public function encode(Claims $claims): Token;

    /**
     * Decode the given token into its claims.
     */
    public function decode(Token $token): Claims;
}
