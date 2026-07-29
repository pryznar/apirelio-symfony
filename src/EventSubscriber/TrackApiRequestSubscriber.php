<?php

declare(strict_types=1);

namespace Tracium\Symfony\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Tracium\Symfony\TraciumManager;

final readonly class TrackApiRequestSubscriber implements EventSubscriberInterface
{
    private const START_ATTRIBUTE = 'tracium.started_at';
    private const CAPTURED_ATTRIBUTE = 'tracium.captured';

    public function __construct(private TraciumManager $tracium) {}

    /** @return array<string, string|array{string, int}> */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 1024],
            KernelEvents::RESPONSE => ['onResponse', -1024],
            KernelEvents::EXCEPTION => ['onException', 1024],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $event->getRequest()->attributes->set(self::START_ATTRIBUTE, hrtime(true));
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->getBoolean(self::CAPTURED_ATTRIBUTE)) {
            return;
        }

        $this->tracium->capture($request, $event->getResponse(), $this->duration($request->attributes->get(self::START_ATTRIBUTE)));
        $request->attributes->set(self::CAPTURED_ATTRIBUTE, true);
    }

    public function onException(ExceptionEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->getBoolean(self::CAPTURED_ATTRIBUTE)) {
            return;
        }

        $exception = $event->getThrowable();
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;
        $this->tracium->capture(
            $request,
            new Response('', $status),
            $this->duration($request->attributes->get(self::START_ATTRIBUTE)),
            $exception,
        );
        $request->attributes->set(self::CAPTURED_ATTRIBUTE, true);
    }

    private function duration(mixed $startedAt): int
    {
        return is_int($startedAt)
            ? (int) round((hrtime(true) - $startedAt) / 1_000_000)
            : 0;
    }
}
