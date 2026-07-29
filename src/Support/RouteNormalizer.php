<?php

declare(strict_types=1);

namespace Tracium\Symfony\Support;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

final readonly class RouteNormalizer
{
    public function __construct(private RouterInterface $router) {}

    public function normalize(Request $request): string
    {
        $name = $this->name($request);
        if ($name !== null) {
            $route = $this->router->getRouteCollection()->get($name);
            if ($route !== null) {
                return $this->withLeadingSlash($route->getPath());
            }
        }

        $segments = explode('/', trim($request->getPathInfo(), '/'));
        $normalized = array_map(static function (string $segment): string {
            if (
                ctype_digit($segment)
                || preg_match('/^[0-9a-f]{8}-[0-9a-f-]{27,}$/i', $segment) === 1
                || preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $segment) === 1
            ) {
                return '{id}';
            }

            return $segment;
        }, $segments);

        return $this->withLeadingSlash(implode('/', $normalized));
    }

    public function name(Request $request): ?string
    {
        $name = $request->attributes->get('_route');

        return is_string($name) && $name !== '' ? $name : null;
    }

    private function withLeadingSlash(string $path): string
    {
        return '/'.ltrim($path, '/');
    }
}
