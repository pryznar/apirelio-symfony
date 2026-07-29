<?php

declare(strict_types=1);

namespace Tracium\Symfony\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Tracium\Symfony\Contracts\ApplicationResolver;
use Tracium\Symfony\Data\TraciumApplication;

final class NullApplicationResolver implements ApplicationResolver
{
    public function resolve(Request $request): TraciumApplication|string|null
    {
        return null;
    }
}
