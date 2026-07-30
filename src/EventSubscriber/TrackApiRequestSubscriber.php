<?php

declare(strict_types=1);

namespace Apirelio\Symfony\EventSubscriber;

use Apirelio\Symfony\ApirelioManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class TrackApiRequestSubscriber implements EventSubscriberInterface
{
    private const START_ATTRIBUTE = 'apirelio.started_at';

    private const CAPTURED_ATTRIBUTE = 'apirelio.captured';

    public function __construct(private ApirelioManager $apirelio) {}

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

        $this->apirelio->capture($request, $event->getResponse(), $this->duration($request->attributes->get(self::START_ATTRIBUTE)));
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
        $this->apirelio->capture(
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
