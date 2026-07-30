<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Apirelio\Symfony\Contracts\ApplicationResolver;
use Apirelio\Symfony\Data\ApirelioApplication;

final class NullApplicationResolver implements ApplicationResolver
{
    public function resolve(Request $request): ApirelioApplication|string|null
    {
        return null;
    }
}
