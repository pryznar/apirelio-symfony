<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Transport;

use Apirelio\Core\Contracts\IngestionClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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
