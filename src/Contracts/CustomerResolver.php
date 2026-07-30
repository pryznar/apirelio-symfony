<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Contracts;

use Symfony\Component\HttpFoundation\Request;
use Apirelio\Symfony\Data\ApirelioCustomer;

interface CustomerResolver
{
    public function resolve(Request $request): ?ApirelioCustomer;
}
