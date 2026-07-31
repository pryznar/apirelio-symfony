<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Transport;

use Apirelio\Core\Config\TransportConfig;
use Apirelio\Symfony\Contracts\EventTransport;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class HttpBatchTransport extends \Apirelio\Core\Transport\HttpBatchTransport implements EventTransport
{
    /** @param array<string, mixed> $config */
    public function __construct(
        HttpClientInterface $http,
        array $config,
    ) {
        parent::__construct(
            new SymfonyIngestionClient($http),
            new TransportConfig(
                endpoint: (string) ($config['endpoint'] ?? 'https://apirelio.com'),
                apiKey: (string) ($config['api_key'] ?? ''),
                timeoutSeconds: (float) ($config['timeout_seconds'] ?? 2),
                connectTimeoutSeconds: (float) ($config['connect_timeout_seconds'] ?? 0.5),
            ),
        );
    }
}
