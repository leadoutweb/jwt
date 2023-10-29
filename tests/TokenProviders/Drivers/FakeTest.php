<?php

namespace Tests\TokenProviders\Drivers;

use Leadout\JWT\TokenProviders\Contract as TokenProvider;
use Leadout\JWT\TokenProviders\Drivers\Fake;
use PHPUnit\Framework\TestCase;

class FakeTest extends TestCase
{
    use ContractTests;

    /**
     * {@inheritdoc}
     */
    protected function getTokenProvider(): TokenProvider
    {
        return new Fake;
    }
}
