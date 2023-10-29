<?php

namespace Leadout\JWT\TokenProviders\Drivers;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\InvalidTokenException;
use Leadout\JWT\Exceptions\TokenExpiredException;
use Leadout\JWT\TokenProviders\Contract;
use UnexpectedValueException;

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
            JWT::encode($claims->all(), $this->getEncodingKey(), $this->getAlgorithm()),
            $claims
        );
    }

    /**
     * {@inheritdoc}
     */
    public function decode(Token $token): Token
    {
        try {
            return new Token(
                $token->getValue(),
                new Claims(
                    (array) JWT::decode(
                        $token->getValue(),
                        $this->getDecodingKey()
                    )
                )
            );
        } catch (ExpiredException) {
            throw new TokenExpiredException;
        } catch (SignatureInvalidException|UnexpectedValueException) {
            throw new InvalidTokenException;
        }
    }

    /**
     * Get the key for encoding tokens.
     */
    private function getEncodingKey(): string
    {
        return $this->config['key'] ?? file_get_contents($this->config['private_key']);
    }

    /**
     * Get the key for decoding tokens.
     */
    private function getDecodingKey(): Key
    {
        return new Key($this->config['key'] ?? file_get_contents($this->config['public_key']), $this->getAlgorithm());
    }

    /**
     * Get the algorithm to use for encoding and decoding tokens.
     */
    private function getAlgorithm(): string
    {
        return isset($this->config['key']) ? 'HS256' : 'RS256';
    }
}
