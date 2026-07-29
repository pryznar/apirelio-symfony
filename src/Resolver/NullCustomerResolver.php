<?php

declare(strict_types=1);

namespace Tracium\Symfony\Resolver;

use Symfony\Component\HttpFoundation\Request;
use Tracium\Symfony\Contracts\CustomerResolver;
use Tracium\Symfony\Data\TraciumCustomer;

final class NullCustomerResolver implements CustomerResolver
{
    public function resolve(Request $request): ?TraciumCustomer
    {
        return null;
    }
}
