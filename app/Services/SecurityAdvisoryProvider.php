<?php

namespace App\Services;

interface SecurityAdvisoryProvider
{
    /**
     * @return iterable<int, array<string, mixed>>
     */
    public function advisories(): iterable;
}
