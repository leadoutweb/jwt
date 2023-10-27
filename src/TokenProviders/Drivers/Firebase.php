<?php

namespace Leadout\JWT\TokenProviders\Drivers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\TokenProviders\Contract;

class Firebase implements Contract
{
    /**
     * The configuration data.
     */
    private array $config;

    /**
     * Instantiate the class.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(Claims $claims): Token
    {
        return new Token(
            JWT::encode($claims->all(), file_get_contents($this->config['private_key']), 'RS256')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function decode(Token $token): Claims
    {
        return new Claims(
            (array)JWT::decode(
                $token->getValue(),
                new Key(file_get_contents($this->config['public_key']), 'RS256')
            )
        );
    }
}
