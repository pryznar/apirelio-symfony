<?php

declare(strict_types=1);

namespace Apirelio\Symfony;

use Apirelio\Core\Data\EventContext;
use Apirelio\Core\ErrorCodeExtractor;
use Apirelio\Core\EventFactory;
use Apirelio\Core\MetadataSanitizer;
use Apirelio\Symfony\Contracts\ApplicationResolver;
use Apirelio\Symfony\Contracts\CustomerResolver;
use Apirelio\Symfony\Contracts\EventTransport;
use Apirelio\Symfony\Data\ApirelioApplication;
use Apirelio\Symfony\Support\RouteNormalizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class ApirelioManager
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private RequestStack $requests,
        private EventTransport $transport,
        private RouteNormalizer $routes,
        private CustomerResolver $customers,
        private ApplicationResolver $applications,
        private array $config,
        private ?LoggerInterface $logger = null,
        private EventFactory $events = new EventFactory,
        private MetadataSanitizer $metadata = new MetadataSanitizer,
        private ErrorCodeExtractor $errorCodes = new ErrorCodeExtractor,
    ) {}

    public function setErrorCode(string $errorCode): self
    {
        $this->requests->getCurrentRequest()?->attributes->set(
            'apirelio.error_code',
            mb_substr($errorCode, 0, 255),
        );

        return $this;
    }

    /** @param array<string, bool|float|int|string|null> $metadata */
    public function addMetadata(array $metadata): self
    {
        $request = $this->requests->getCurrentRequest();
        if ($request === null) {
            return $this;
        }

        /** @var array<string, bool|float|int|string|null> $current */
        $current = $request->attributes->get('apirelio.metadata', []);
        $request->attributes->set('apirelio.metadata', array_merge($current, $this->sanitizeMetadata($metadata)));

        return $this;
    }

    public function capture(
        Request $request,
        ?Response $response,
        int $durationMilliseconds,
        ?Throwable $exception = null,
    ): void {
        if (! $this->shouldCapture($request)) {
            return;
        }

        try {
            $customer = $this->customers->resolve($request);
            $application = $this->applications->resolve($request);
            if (is_string($application)) {
                $application = new ApirelioApplication($application);
            }
            $metadata = $this->requestMetadata($request);
            if ($exception !== null) {
                $metadata['exception'] = $exception::class;
            }
            $metadata = $this->sanitizeMetadata($metadata);

            $this->transport->send([$this->events->create(new EventContext(
                service: (string) $this->config['service'],
                environment: (string) $this->config['environment'],
                method: $request->getMethod(),
                route: $this->routes->normalize($request),
                routeName: $this->routes->name($request),
                status: $response?->getStatusCode() ?? 500,
                durationMilliseconds: $durationMilliseconds,
                requestBytes: (int) $request->headers->get('content-length', '0'),
                responseBytes: $this->responseBytes($response),
                customer: $customer,
                application: $application,
                apiVersion: $request->headers->get('x-api-version'),
                sdk: 'symfony',
                sdkVersion: '0.1.0',
                release: is_string($this->config['release']) ? $this->config['release'] : null,
                errorCode: $this->errorCode($request, $response),
                metadata: $metadata,
            ))]);
        } catch (Throwable $throwable) {
            try {
                $this->logger?->warning('Apirelio event capture failed.', [
                    'exception' => $throwable,
                ]);
            } catch (Throwable) {
                // Analytics must never alter the application response.
            }
        } finally {
            $request->attributes->remove('apirelio.error_code');
            $request->attributes->remove('apirelio.metadata');
        }
    }

    private function shouldCapture(Request $request): bool
    {
        if (! (bool) $this->config['enabled'] || (string) $this->config['api_key'] === '') {
            return false;
        }

        /** @var list<string> $paths */
        $paths = $this->config['paths'];
        $path = $request->getPathInfo();

        foreach ($paths as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, bool|float|int|string|null> */
    private function requestMetadata(Request $request): array
    {
        /** @var array<string, bool|float|int|string|null> $metadata */
        $metadata = $request->attributes->get('apirelio.metadata', []);
        /** @var list<string> $headers */
        $headers = $this->config['capture_headers'];

        foreach ($headers as $header) {
            $value = $request->headers->get($header);
            if (is_string($value) && $value !== '') {
                $metadata['header.'.strtolower($header)] = mb_substr($value, 0, 500);
            }
        }

        return $metadata;
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $metadata
     * @return array<string, bool|float|int|string|null>
     */
    private function sanitizeMetadata(array $metadata): array
    {
        /** @var list<string> $allowed */
        $allowed = $this->config['metadata_keys'];

        return $this->metadata->sanitize($metadata, $allowed);
    }

    private function errorCode(Request $request, ?Response $response): ?string
    {
        $explicit = $request->attributes->get('apirelio.error_code');
        $content = $response?->getContent();

        return $this->errorCodes->extract(
            is_string($explicit) ? $explicit : null,
            is_string($content) ? $content : null,
            (string) $this->config['error_code_json_path'],
        );
    }

    private function responseBytes(?Response $response): int
    {
        $content = $response?->getContent();

        return is_string($content) ? strlen($content) : 0;
    }
}
