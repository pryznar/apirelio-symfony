<?php

declare(strict_types=1);

namespace Tracium\Symfony\Transport;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tracium\Core\Config\TransportConfig;
use Tracium\Symfony\Contracts\EventTransport;

final class HttpBatchTransport extends \Tracium\Core\Transport\HttpBatchTransport implements EventTransport
{
    /** @param array<string, mixed> $config */
    public function __construct(
        HttpClientInterface $http,
        array $config,
    ) {
        parent::__construct(
            new SymfonyIngestionClient($http),
            new TransportConfig(
                endpoint: (string) ($config['endpoint'] ?? 'https://ingest.tracium.example'),
                apiKey: (string) ($config['api_key'] ?? ''),
                timeoutSeconds: (float) ($config['timeout_seconds'] ?? 2),
                connectTimeoutSeconds: (float) ($config['connect_timeout_seconds'] ?? 0.5),
            ),
        );
    }
}
