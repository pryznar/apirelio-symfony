<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Message;

final readonly class BufferApirelioEvents
{
    /** @param list<array<string, mixed>> $events */
    public function __construct(public array $events) {}
}
