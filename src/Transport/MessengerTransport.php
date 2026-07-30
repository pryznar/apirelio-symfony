<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Transport;

use Symfony\Component\Messenger\MessageBusInterface;
use Apirelio\Symfony\Contracts\EventTransport;
use Apirelio\Symfony\Message\BufferApirelioEvents;

final readonly class MessengerTransport implements EventTransport
{
    public function __construct(private MessageBusInterface $bus) {}

    public function send(array $events): void
    {
        if ($events !== []) {
            $this->bus->dispatch(new BufferApirelioEvents($events));
        }
    }
}
