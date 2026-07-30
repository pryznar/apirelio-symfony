<?php

declare(strict_types=1);

namespace Apirelio\Symfony\Tests;

use Apirelio\Symfony\Command\FlushBufferCommand;
use Apirelio\Symfony\Contracts\EventTransport;
use Apirelio\Symfony\DependencyInjection\ApirelioExtension;
use Apirelio\Symfony\EventSubscriber\TrackApiRequestSubscriber;
use Apirelio\Symfony\MessageHandler\BufferApirelioEventsHandler;
use Apirelio\Symfony\Transport\HttpBatchTransport;
use Apirelio\Symfony\Transport\MessengerTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ApirelioExtensionTest extends TestCase
{
    public function test_it_registers_the_sync_transport_and_framework_integrations(): void
    {
        $container = new ContainerBuilder;
        $container->setParameter('kernel.cache_dir', '/tmp/symfony-cache');

        (new ApirelioExtension)->load([[
            'api_key' => 'apr_test_secret',
            'transport' => 'sync',
        ]], $container);

        self::assertSame(HttpBatchTransport::class, (string) $container->getAlias(EventTransport::class));
        self::assertTrue($container->hasDefinition(TrackApiRequestSubscriber::class));
        self::assertTrue($container->hasDefinition(BufferApirelioEventsHandler::class));
        self::assertTrue($container->hasDefinition(FlushBufferCommand::class));
        self::assertSame(
            ['kernel.event_subscriber' => [[]]],
            $container->getDefinition(TrackApiRequestSubscriber::class)->getTags(),
        );
    }

    public function test_it_registers_the_configured_messenger_transport(): void
    {
        $container = new ContainerBuilder;
        $container->setParameter('kernel.cache_dir', '/tmp/symfony-cache');

        (new ApirelioExtension)->load([[
            'api_key' => 'apr_test_secret',
            'transport' => 'messenger',
            'messenger_bus' => 'command.bus',
        ]], $container);

        self::assertSame(MessengerTransport::class, (string) $container->getAlias(EventTransport::class));
        self::assertSame(
            'command.bus',
            (string) $container->getDefinition(MessengerTransport::class)->getArgument('$bus'),
        );
    }
}
