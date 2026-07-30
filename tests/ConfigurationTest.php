<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Apirelio\Symfony\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    public function test_it_exposes_safe_production_defaults(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertTrue($config['enabled']);
        self::assertSame('messenger', $config['transport']);
        self::assertSame(['/api/*'], $config['paths']);
        self::assertSame(500, $config['batch_size']);
        self::assertSame(['x-api-version', 'x-sdk-version', 'user-agent'], $config['capture_headers']);
    }
}
