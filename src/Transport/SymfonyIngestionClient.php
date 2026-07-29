<?php

declare(strict_types=1);

namespace Tracium\Symfony\Transport;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tracium\Core\Contracts\IngestionClient;

final readonly class SymfonyIngestionClient implements IngestionClient
{
    public function __construct(private HttpClientInterface $http) {}

    public function postBatch(
        string $endpoint,
        string $apiKey,
        array $events,
        float $timeoutSeconds,
        float $connectTimeoutSeconds,
    ): void {
        $this->http->request('POST', $endpoint, [
            'auth_bearer' => $apiKey,
            'headers' => ['Accept' => 'application/json'],
            'json' => ['events' => $events],
            'timeout' => $timeoutSeconds,
            'max_duration' => $timeoutSeconds,
        ])->getContent();
    }
}
