<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Apirelio\Symfony\Transport\HttpBatchTransport;

final class HttpBatchTransportTest extends TestCase
{
    public function test_it_sends_an_authenticated_batch(): void
    {
        $capturedOptions = [];
        $client = new MockHttpClient(
            static function (string $method, string $url, array $options) use (&$capturedOptions): MockResponse {
                $capturedOptions = $options;
                self::assertSame('POST', $method);
                self::assertSame('https://ingest.apirelio.test/ingest/v1/events/batch', $url);

                return new MockResponse('{"accepted":1}', ['http_code' => 202]);
            },
        );
        $transport = new HttpBatchTransport($client, [
            'endpoint' => 'https://ingest.apirelio.test',
            'api_key' => 'apr_test_secret',
            'timeout_seconds' => 2.0,
        ]);

        $transport->send([['event_id' => 'evt_1']]);

        self::assertSame('Authorization: Bearer apr_test_secret', $capturedOptions['normalized_headers']['authorization'][0]);
        self::assertStringContainsString('"event_id":"evt_1"', (string) $capturedOptions['body']);
    }
}
