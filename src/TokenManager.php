<?php

namespace Leadout\JWT;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\TokenExpiredException;
use Leadout\JWT\Exceptions\TokenBlacklistedException;
use Leadout\JWT\Exceptions\TokenNotProvidedException;
use Leadout\JWT\Blacklists\Contract as Blacklist;
use Leadout\JWT\TokenProviders\Contract as TokenProvider;
use Ramsey\Uuid\Uuid;

class TokenManager
{
    /**
     * The token provider.
     */
    private TokenProvider $tokenProvider;

    /**
     * The token blacklist.
     */
    private Blacklist $blacklist;

    /**
     * The configuration data.
     */
    private array $config;

    /**
     * Instantiate the class.
     */
    public function __construct(TokenProvider $tokenProvider, Blacklist $blacklist, array $config)
    {
        $this->tokenProvider = $tokenProvider;

        $this->blacklist = $blacklist;

        $this->config = $config;
    }

    /**
     * Issue a new token for the given user.
     */
    public function issue(Authenticatable $user): Token
    {
        return $this->tokenProvider->encode($this->getClaims($user));
    }

    /**
     * Get the claims for a token for the given user.
     */
    private function getClaims(Authenticatable $user): Claims
    {
        $value = [
            'jti' => Uuid::uuid4()->toString(),
            'iat' => Carbon::now()->timestamp,
            'nbf' => Carbon::now()->timestamp,
            'exp' => Carbon::now()->addHour()->timestamp,
            'sub' => $user->getAuthIdentifier(),
            ...$this->config['claims'] ?? []
        ];

        if (method_exists($user, 'getClaims')) {
            $value = [...$value, $user->getClaims()];
        }

        return new Claims($value);
    }

    /**
     * Get the claims in the token contained in the given request.
     */
    public function claims(Request $request): Claims
    {
        return $this->guard(
            $this->tokenProvider->decode($this->getToken($request))
        );
    }

    /**
     * Guard that the given claims are valid.
     */
    private function guard(Claims $claims): Claims
    {
        if ($this->isBlacklisted($claims->jti())) {
            throw new TokenBlacklistedException;
        }

        if ($this->isExpired($claims->exp())) {
            throw new TokenExpiredException;
        }

        return $claims;
    }

    /**
     * Determine if the token identified by the given token ID has been blacklisted.
     */
    private function isBlacklisted(string $jti): bool
    {
        return $this->blacklist->has($jti);
    }

    /**
     * Determine if the given timestamp is in the past.
     */
    private function isExpired(string $exp): bool
    {
        return Carbon::createFromTimestamp($exp)->isPast();
    }

    /**
     * Invalidate the token contained in the given request.
     */
    public function invalidate(Request $request): void
    {
        $claims = $this->claims($request);

        $this->blacklist->put($claims->jti(), $claims->exp() - Carbon::now()->timestamp);
    }

    /**
     * Get the token contained in the given request.
     */
    private function getToken(Request $request): Token
    {
        if (!$request->bearerToken()) {
            throw new TokenNotProvidedException;
        }

        return new Token($request->bearerToken());
    }

    /**
     * Get the token provider.
     */
    public function getTokenProvider(): TokenProvider
    {
        return $this->tokenProvider;
    }
}
