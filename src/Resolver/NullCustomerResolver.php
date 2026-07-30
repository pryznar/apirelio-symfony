<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Apirelio\Symfony\Contracts\CustomerResolver;
use Apirelio\Symfony\Data\ApirelioCustomer;

final class NullCustomerResolver implements CustomerResolver
{
    public function resolve(Request $request): ?ApirelioCustomer
    {
        return null;
    }
}
