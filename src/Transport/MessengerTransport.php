<?php

declare(strict_types=1);

namespace Tracium\Symfony\Transport;

use Symfony\Component\Messenger\MessageBusInterface;
use Tracium\Symfony\Contracts\EventTransport;
use Tracium\Symfony\Message\BufferTraciumEvents;

final readonly class MessengerTransport implements EventTransport
{
    public function __construct(private MessageBusInterface $bus) {}

    public function send(array $events): void
    {
        if ($events !== []) {
            $this->bus->dispatch(new BufferTraciumEvents($events));
        }
    }
}
