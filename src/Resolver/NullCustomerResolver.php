<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Resolver;

use Apirelio\Symfony\Contracts\CustomerResolver;
use Apirelio\Symfony\Data\ApirelioCustomer;
use Symfony\Component\HttpFoundation\Request;

final class NullCustomerResolver implements CustomerResolver
{
    public function resolve(Request $request): ?ApirelioCustomer
    {
        return null;
    }
}
