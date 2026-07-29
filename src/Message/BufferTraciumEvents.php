<?php

declare(strict_types=1);

namespace Tracium\Symfony\Message;

final readonly class BufferTraciumEvents
{
    /** @param list<array<string, mixed>> $events */
    public function __construct(public array $events) {}
}
