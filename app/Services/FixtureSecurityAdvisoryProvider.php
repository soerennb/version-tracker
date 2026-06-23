<?php

namespace App\Services;

class FixtureSecurityAdvisoryProvider implements SecurityAdvisoryProvider
{
    /**
     * @param  iterable<int, array<string, mixed>>  $advisories
     */
    public function __construct(
        protected iterable $advisories,
    ) {}

    /**
     * @return iterable<int, array<string, mixed>>
     */
    public function advisories(): iterable
    {
        return $this->advisories;
    }
}
