<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class OsvClient
{
    /**
     * @param  array<int, array{package:array{purl:string},version?:string}>  $queries
     * @return array<int, array<string, mixed>>
     */
    public function queryBatch(array $queries): array
    {
        if ($queries === []) {
            return [];
        }

        /** @var array<int, array<string, mixed>> $results */
        $results = $this->request()
            ->post((string) config('services.osv.url'), ['queries' => $queries])
            ->throw()
            ->json('results', []);

        return $results;
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout(max((int) config('services.osv.timeout', 10), 1))
            ->retry(max((int) config('services.osv.retry_times', 2), 0), 200, throw: false);
    }
}
