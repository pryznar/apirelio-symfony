<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Contracts;

use Apirelio\Symfony\Data\ApirelioApplication;
use Symfony\Component\HttpFoundation\Request;

interface ApplicationResolver
{
    public function resolve(Request $request): ApirelioApplication|string|null;
}
