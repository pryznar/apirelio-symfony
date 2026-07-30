<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Contracts;

use Apirelio\Symfony\Data\ApirelioCustomer;
use Symfony\Component\HttpFoundation\Request;

interface CustomerResolver
{
    public function resolve(Request $request): ?ApirelioCustomer;
}
