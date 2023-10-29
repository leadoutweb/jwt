<?php

namespace Leadout\JWT;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Leadout\JWT\Blacklists\Contract as Blacklist;
use Leadout\JWT\Entities\Claims;
use Leadout\JWT\Entities\Token;
use Leadout\JWT\Exceptions\InvalidAudienceException;
use Leadout\JWT\Exceptions\TokenExpiredException;
use Leadout\JWT\Exceptions\TokenInvalidatedException;
use Leadout\JWT\Exceptions\TokenNotProvidedException;
use Leadout\JWT\TokenProviders\Contract as TokenProvider;

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
     * The audience that the token manager is for.
     */
    private string $aud;

    /**
     * The configuration data.
     */
    private array $config;

    /**
     * Instantiate the class.
     */
    public function __construct(TokenProvider $tokenProvider, Blacklist $blacklist, string $aud, array $config)
    {
        $this->tokenProvider = $tokenProvider;

        $this->blacklist = $blacklist;

        $this->aud = $aud;

        $this->config = $config;
    }

    /**
     * Issue a new token for the given user.
     */
    public function issue(Authenticatable $user): Token
    {
        return $this->tokenProvider->encode($this->getClaimsForUser($user));
    }

    /**
     * Get the claims for the given user.
     */
    private function getClaimsForUser(Authenticatable $user): Claims
    {
        $claims = [
            'sub' => $user->getAuthIdentifier(),
            ...$this->getCommonClaims(),
        ];

        if (method_exists($user, 'getClaims')) {
            $claims = [...$claims, ...$user->getClaims()];
        }

        return new Claims($claims);
    }

    /**
     * Get the common claims for a token.
     */
    private function getCommonClaims(): array
    {
        return [
            'aud' => $this->aud,
            'jti' => Str::uuid()->toString(),
            'iat' => Carbon::now()->timestamp,
            'nbf' => Carbon::now()->timestamp,
            'exp' => Carbon::now()->addMinutes($this->config['ttl'] ?? 60)->timestamp,
            ...$this->config['claims'] ?? [],
        ];
    }

    /**
     * Decode the token contained in the given request.
     */
    public function decode(Request $request): Token
    {
        return $this->guard($this->tokenProvider->decode($this->getEncodedToken($request)));
    }

    /**
     * Guard that the given token is valid.
     */
    private function guard(Token $token): Token
    {
        if ($this->isBlacklisted($token->claims()->jti())) {
            throw new TokenInvalidatedException;
        }

        if ($this->isExpired($token->claims()->exp())) {
            throw new TokenExpiredException;
        }

        if ($token->claims()->aud() != $this->aud) {
            throw new InvalidAudienceException;
        }

        return $token;
    }

    /**
     * Determine if the token identified by the given token ID has been invalidated.
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
     * Refresh the token contained in the given request.
     */
    public function refresh(Request $request): Token
    {
        return $this->tokenProvider->encode(
            new Claims([
                ...$this->decode($request)->claims()->all(),
                ...$this->getCommonClaims(),
            ])
        );
    }

    /**
     * Invalidate the token contained in the given request.
     */
    public function invalidate(Request $request): void
    {
        $token = $this->decode($request);

        $this->blacklist->put($token->claims()->jti(), $token->claims()->exp() - Carbon::now()->timestamp);
    }

    /**
     * Get the token contained in the given request.
     */
    private function getEncodedToken(Request $request): Token
    {
        if (! $request->bearerToken()) {
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
