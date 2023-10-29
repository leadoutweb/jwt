<?php

namespace Leadout\JWT\Blacklists\Drivers;

use Illuminate\Contracts\Cache\Repository;
use Leadout\JWT\Blacklists\Contract;
use Psr\SimpleCache\InvalidArgumentException;

class Cache implements Contract
{
    /**
     * The cache repository.
     */
    private Repository $cache;

    /**
     * Instantiate the class.
     */
    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $jti, ?int $ttl): void
    {
        $this->cache->put($this->key($jti), true, $ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $jti): bool
    {
        try {
            return $this->cache->has($this->key($jti));
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Get the key for the token identified by the given ID.
     */
    private function key(string $jti): string
    {
        return 'tokens.'.$jti.'.invalidated';
    }
}
