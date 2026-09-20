<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Tests;

use Apirelio\Symfony\ApirelioManager;
use Apirelio\Symfony\Contracts\ApplicationResolver;
use Apirelio\Symfony\Contracts\CustomerResolver;
use Apirelio\Symfony\Contracts\EventTransport;
use Apirelio\Symfony\Data\ApirelioApplication;
use Apirelio\Symfony\Data\ApirelioCustomer;
use Apirelio\Symfony\Support\RouteNormalizer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

final class ApirelioManagerTest extends TestCase
{
    public function test_it_captures_the_shared_privacy_safe_event_contract(): void
    {
        $transport = new class implements EventTransport
        {
            /** @var list<array<string, mixed>> */
            public array $events = [];

            public function send(array $events): void
            {
                $this->events = [...$this->events, ...$events];
            }
        };
        $request = Request::create(
            '/api/invoices/123?secret=hidden',
            'POST',
            [],
            ['session' => 'private'],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_API_VERSION' => 'v2'],
            '{"card_number":"never capture"}',
        );
        $request->attributes->set('_route', 'invoice.create');
        $stack = new RequestStack;
        $stack->push($request);
        $manager = $this->manager($stack, $transport);

        $manager->addMetadata([
            'region' => 'eu-central',
            'secret' => 'discarded',
            'api_token' => 'also-discarded',
        ]);
        $manager->setErrorCode('VALIDATION_FAILED');
        $manager->capture($request, new Response('{"error":{"code":"OTHER"}}', 422), 37);

        self::assertCount(1, $transport->events);
        $event = $transport->events[0];
        self::assertSame('/api/invoices/{invoice}', $event['route']);
        self::assertSame('invoice.create', $event['route_name']);
        self::assertSame(422, $event['status']);
        self::assertSame('customer_42', $event['customer_id']);
        self::assertSame('billing-production', $event['application_id']);
        self::assertSame('VALIDATION_FAILED', $event['error_code']);
        self::assertSame('v2', $event['api_version']);
        self::assertSame('1.0.1', $event['sdk_version']);
        self::assertSame(['region' => 'eu-central', 'header.x-api-version' => 'v2'], $event['metadata']);
        self::assertStringNotContainsString('secret', json_encode($event, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('card_number', json_encode($event, JSON_THROW_ON_ERROR));
    }

    public function test_transport_failure_never_escapes_into_the_application(): void
    {
        $transport = new class implements EventTransport
        {
            public function send(array $events): void
            {
                throw new RuntimeException('Network unavailable');
            }
        };
        $request = Request::create('/api/invoices', 'GET');
        $request->attributes->set('_route', 'invoice.index');
        $stack = new RequestStack;
        $stack->push($request);

        $this->manager($stack, $transport)->capture($request, new Response('OK'), 5);
        $this->addToAssertionCount(1);
    }

    private function manager(RequestStack $stack, EventTransport $transport): ApirelioManager
    {
        $routes = new RouteCollection;
        $routes->add('invoice.create', new Route('/api/invoices/{invoice}'));
        $routes->add('invoice.index', new Route('/api/invoices'));
        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);
        $customers = new class implements CustomerResolver
        {
            public function resolve(Request $request): ApirelioCustomer
            {
                return new ApirelioCustomer('customer_42', 'Acme Europe', 'growth');
            }
        };
        $applications = new class implements ApplicationResolver
        {
            public function resolve(Request $request): ApirelioApplication
            {
                return new ApirelioApplication('billing-production', 'Billing Production');
            }
        };

        return new ApirelioManager(
            $stack,
            $transport,
            new RouteNormalizer($router),
            $customers,
            $applications,
            [
                'enabled' => true,
                'api_key' => 'apr_test_secret',
                'endpoint' => 'https://ingest.apirelio.test',
                'service' => 'billing-api',
                'environment' => 'production',
                'release' => '2026.07.29.1',
                'paths' => ['/api/*'],
                'capture_headers' => ['x-api-version'],
                'metadata_keys' => ['region', 'api_token'],
                'error_code_json_path' => 'error.code',
            ],
            new NullLogger,
        );
    }
}
