<?php

namespace Leadout\JWT\Blacklists;

interface Contract
{
    /**
     * Invalidate the token identified by the given ID for the given number of seconds.
     */
    public function put(string $jti, ?int $ttl): void;

    /**
     * Determine if the token identified by the given ID is in the repository.
     */
    public function has(string $jti): bool;
}
