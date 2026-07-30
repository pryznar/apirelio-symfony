<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Transport;

use Apirelio\Core\Config\BufferConfig;
use Apirelio\Symfony\Contracts\EventTransport;

final class FileBufferTransport extends \Apirelio\Core\Transport\FileBufferTransport implements EventTransport
{
    /** @param array<string, mixed> $config */
    public function __construct(
        HttpBatchTransport $http,
        array $config,
    ) {
        parent::__construct(
            $http,
            new BufferConfig(
                path: (string) $config['buffer_path'],
                batchSize: (int) $config['batch_size'],
                flushIntervalSeconds: (int) $config['flush_interval_seconds'],
            ),
        );
    }
}
