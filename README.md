# Tracium Symfony SDK

Fail-safe customer integration analytics for Symfony APIs. The bundle records
normalized endpoint metrics and customer context without capturing request or
response bodies, credentials, cookies, query strings, IP addresses, or personal
data. The shared event contract, privacy rules and delivery primitives come
from `tracium/php-core`, installed automatically by Composer.

## Requirements

- PHP 8.2, 8.3 or 8.4
- Symfony 6.4, 7.4 or 8.x

Symfony 8 itself requires PHP 8.4. Symfony 6.4 and 7.4 can be used on PHP 8.2.

## Installation

```bash
composer require tracium/symfony:^0.1
```

Register the bundle:

```php
// config/bundles.php
return [
    // ...
    Tracium\Symfony\TraciumBundle::class => ['all' => true],
];
```

Configure the project key shown once in the Tracium dashboard:

```dotenv
TRACIUM_ENABLED=true
TRACIUM_ENDPOINT=https://ingest.tracium.example
TRACIUM_API_KEY=trc_live_xxxxxxxxx
TRACIUM_SERVICE=billing-api
TRACIUM_ENVIRONMENT=production
TRACIUM_RELEASE=2026.07.29.1
```

```yaml
# config/packages/tracium.yaml
tracium:
    enabled: '%env(bool:TRACIUM_ENABLED)%'
    endpoint: '%env(TRACIUM_ENDPOINT)%'
    api_key: '%env(TRACIUM_API_KEY)%'
    service: '%env(TRACIUM_SERVICE)%'
    environment: '%env(TRACIUM_ENVIRONMENT)%'
    release: '%env(TRACIUM_RELEASE)%'
    transport: messenger
    paths:
        - /api/*
```

The subscriber is registered automatically and records matching main requests.
Route templates are used instead of concrete URLs, so `/api/invoices/123`
becomes `/api/invoices/{invoice}` and query strings are never sent.

## Messenger transport

The default `messenger` transport moves network delivery outside the customer
request. Route the SDK message to an asynchronous transport:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            'Tracium\Symfony\Message\BufferTraciumEvents': async
```

Run your normal Messenger worker:

```bash
php bin/console messenger:consume async
```

Failed ingestion requests throw inside the worker so Symfony Messenger can
apply its configured retry and failure transport policies.

## Customer and application identity

Create application-specific resolvers:

```php
<?php

namespace App\Tracium;

use Symfony\Component\HttpFoundation\Request;
use Tracium\Symfony\Contracts\CustomerResolver;
use Tracium\Symfony\Data\TraciumCustomer;

final class ApiCustomerResolver implements CustomerResolver
{
    public function resolve(Request $request): ?TraciumCustomer
    {
        $client = $request->attributes->get('api_client');

        return $client === null
            ? null
            : new TraciumCustomer(
                id: (string) $client->companyId,
                name: $client->companyName,
                plan: $client->plan,
            );
    }
}
```

```php
<?php

namespace App\Tracium;

use Symfony\Component\HttpFoundation\Request;
use Tracium\Symfony\Contracts\ApplicationResolver;
use Tracium\Symfony\Data\TraciumApplication;

final class ApiApplicationResolver implements ApplicationResolver
{
    public function resolve(Request $request): ?TraciumApplication
    {
        $client = $request->attributes->get('api_client');

        return $client === null
            ? null
            : new TraciumApplication(
                id: (string) $client->id,
                name: $client->name,
            );
    }
}
```

Bind them to the SDK contracts:

```yaml
# config/services.yaml
services:
    Tracium\Symfony\Contracts\CustomerResolver:
        alias: App\Tracium\ApiCustomerResolver

    Tracium\Symfony\Contracts\ApplicationResolver:
        alias: App\Tracium\ApiApplicationResolver
```

## Error codes and metadata

JSON error codes are read from `error.code` by default. Override a code or add
allow-listed metadata during the current request:

```php
use Tracium\Symfony\TraciumManager;

final class SendInvoiceController
{
    public function __invoke(TraciumManager $tracium): Response
    {
        $tracium
            ->setErrorCode('INVALID_CURRENCY')
            ->addMetadata([
                'integration_type' => 'accounting',
                'region' => 'eu-central',
            ]);

        // ...
    }
}
```

Declare allowed custom keys in the bundle configuration:

```yaml
tracium:
    metadata_keys: [integration_type, region]
```

Unlisted keys and non-scalar values are discarded.

## Transports and buffering

- `messenger` (default): dispatch locally and deliver from an async worker.
- `sync`: deliver immediately; intended for local diagnostics.
- `file_buffer`: append to a locked local NDJSON buffer and flush by batch size
  or interval.

For `file_buffer`, schedule a forced flush so low-traffic applications do not
leave a partial batch behind:

```bash
php bin/console tracium:flush
```

Useful options:

```yaml
tracium:
    transport: messenger
    messenger_bus: messenger.default_bus
    timeout_seconds: 2
    connect_timeout_seconds: 0.5
    batch_size: 500
    flush_interval_seconds: 10
    buffer_path: '%kernel.cache_dir%/tracium/events.ndjson'
    error_code_json_path: error.code
    capture_headers: [x-api-version, x-sdk-version, user-agent]
```

Resolver, transport, and logger failures are caught at the request boundary.
Analytics may lose an event, but it never changes the customer API response or
masks its exception.
