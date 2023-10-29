<?php

namespace Tests\Blacklists\Drivers;

use Leadout\JWT\Blacklists\Contract;
use Leadout\JWT\Blacklists\Drivers\Fake;
use PHPUnit\Framework\TestCase;

class FakeTest extends TestCase
{
    use ContractTests;

    /**
     * {@inheritDoc}
     */
    protected function getBlacklist(): Contract
    {
        return new Fake;
    }
}
