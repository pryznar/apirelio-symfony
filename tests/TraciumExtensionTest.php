<?php

declare(strict_types=1);

namespace Tracium\Symfony\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tracium\Symfony\Command\FlushBufferCommand;
use Tracium\Symfony\Contracts\EventTransport;
use Tracium\Symfony\DependencyInjection\TraciumExtension;
use Tracium\Symfony\EventSubscriber\TrackApiRequestSubscriber;
use Tracium\Symfony\MessageHandler\BufferTraciumEventsHandler;
use Tracium\Symfony\Transport\HttpBatchTransport;
use Tracium\Symfony\Transport\MessengerTransport;

final class TraciumExtensionTest extends TestCase
{
    public function test_it_registers_the_sync_transport_and_framework_integrations(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.cache_dir', '/tmp/symfony-cache');

        (new TraciumExtension())->load([[
            'api_key' => 'trc_test_secret',
            'transport' => 'sync',
        ]], $container);

        self::assertSame(HttpBatchTransport::class, (string) $container->getAlias(EventTransport::class));
        self::assertTrue($container->hasDefinition(TrackApiRequestSubscriber::class));
        self::assertTrue($container->hasDefinition(BufferTraciumEventsHandler::class));
        self::assertTrue($container->hasDefinition(FlushBufferCommand::class));
        self::assertSame(
            ['kernel.event_subscriber' => [[]]],
            $container->getDefinition(TrackApiRequestSubscriber::class)->getTags(),
        );
    }

    public function test_it_registers_the_configured_messenger_transport(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.cache_dir', '/tmp/symfony-cache');

        (new TraciumExtension())->load([[
            'api_key' => 'trc_test_secret',
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
