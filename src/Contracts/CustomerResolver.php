<?php

declare(strict_types=1);

namespace Tracium\Symfony\Contracts;

use Symfony\Component\HttpFoundation\Request;
use Tracium\Symfony\Data\TraciumCustomer;

interface CustomerResolver
{
    public function resolve(Request $request): ?TraciumCustomer;
}
