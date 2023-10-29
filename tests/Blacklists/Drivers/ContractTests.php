<?php

namespace Tests\Blacklists\Drivers;

use Leadout\JWT\Blacklists\Contract;

trait ContractTests
{
    /**
     * The blacklist to test.
     */
    protected Contract $blacklist;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->blacklist = $this->getBlacklist();
    }

    /** @test */
    public function can_store_a_jti_in_the_blacklist_indefinitely()
    {
        $this->assertFalse($this->blacklist->has('ABC-123'));

        $this->blacklist->put('ABC-123', null);

        $this->assertTrue($this->blacklist->has('ABC-123'));
    }

    /**
     * Get the blacklist to test.
     */
    abstract protected function getBlacklist(): Contract;
}
