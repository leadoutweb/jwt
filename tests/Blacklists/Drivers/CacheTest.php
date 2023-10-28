<?php

namespace Tests\Blacklists\Drivers;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Leadout\JWT\Blacklists\Contract;
use Leadout\JWT\Blacklists\Drivers\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    use ContractTests;

    /**
     * @inheritDoc
     */
    protected function getBlacklist(): Contract
    {
        return new Cache(new Repository(new ArrayStore));
    }
}
