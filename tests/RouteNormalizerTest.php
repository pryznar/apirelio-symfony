<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Apirelio\Symfony\Support\RouteNormalizer;

final class RouteNormalizerTest extends TestCase
{
    public function test_it_uses_the_symfony_route_template(): void
    {
        $routes = new RouteCollection();
        $routes->add('invoice.show', new Route('/api/invoices/{invoice}'));
        $router = $this->createMock(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);
        $request = Request::create('/api/invoices/123?token=secret');
        $request->attributes->set('_route', 'invoice.show');

        $normalizer = new RouteNormalizer($router);

        self::assertSame('/api/invoices/{invoice}', $normalizer->normalize($request));
        self::assertSame('invoice.show', $normalizer->name($request));
    }

    public function test_it_redacts_identifiers_without_a_matched_route(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $request = Request::create('/api/invoices/123/550e8400-e29b-41d4-a716-446655440000');

        self::assertSame(
            '/api/invoices/{id}/{id}',
            (new RouteNormalizer($router))->normalize($request),
        );
    }
}
