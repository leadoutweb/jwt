<?php

namespace Leadout\JWT\TokenProviders\Drivers;

use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\InvalidTokenException;
use Leadout\JWT\TokenProviders\Contract;

class Fake implements Contract
{
    /**
     * {@inheritDoc}
     */
    public function encode(Claims $claims): Token
    {
        return new Token(base64_encode(json_encode($claims->all())), $claims);
    }

    /**
     * {@inheritDoc}
     */
    public function decode(Token $token): Token
    {
        if (($decoded = json_decode(base64_decode($token->getValue()))) !== null) {
            return new Token($token->getValue(), new Claims((array) $decoded));
        }

        throw new InvalidTokenException;
    }
}
