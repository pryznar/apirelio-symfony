<?php

declare(strict_types=1);

namespace Apirelio\Symfony\MessageHandler;

use Apirelio\Symfony\Message\BufferApirelioEvents;
use Apirelio\Symfony\Transport\HttpBatchTransport;

final readonly class BufferApirelioEventsHandler
{
    public function __construct(private HttpBatchTransport $transport) {}

    public function __invoke(BufferApirelioEvents $message): void
    {
        $this->transport->send($message->events);
    }
}
