<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Contracts;

use Symfony\Component\HttpFoundation\Request;
use Apirelio\Symfony\Data\ApirelioApplication;

interface ApplicationResolver
{
    public function resolve(Request $request): ApirelioApplication|string|null;
}
