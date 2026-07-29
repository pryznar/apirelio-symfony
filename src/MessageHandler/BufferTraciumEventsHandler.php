<?php

declare(strict_types=1);

namespace Tracium\Symfony\MessageHandler;

use Tracium\Symfony\Message\BufferTraciumEvents;
use Tracium\Symfony\Transport\HttpBatchTransport;

final readonly class BufferTraciumEventsHandler
{
    public function __construct(private HttpBatchTransport $transport) {}

    public function __invoke(BufferTraciumEvents $message): void
    {
        $this->transport->send($message->events);
    }
}
