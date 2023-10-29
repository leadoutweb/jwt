<?php

namespace Leadout\JWT\Blacklists\Drivers;

use Illuminate\Support\Collection;
use Leadout\JWT\Blacklists\Contract;

class Fake implements Contract
{
    /**
     * The token blacklist.
     */
    private Collection $blacklist;

    /**
     * Instantiate the class.
     */
    public function __construct()
    {
        $this->blacklist = collect();
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $jti, ?int $ttl): void
    {
        $this->blacklist->push($jti);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $jti): bool
    {
        return $this->blacklist->contains($jti);
    }
}
