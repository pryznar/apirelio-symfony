<?php

declare(strict_types=1);

namespace Apirelio\Symfony\DependencyInjection;

use Apirelio\Symfony\ApirelioManager;
use Apirelio\Symfony\Command\FlushBufferCommand;
use Apirelio\Symfony\Contracts\ApplicationResolver;
use Apirelio\Symfony\Contracts\CustomerResolver;
use Apirelio\Symfony\Contracts\EventTransport;
use Apirelio\Symfony\EventSubscriber\TrackApiRequestSubscriber;
use Apirelio\Symfony\MessageHandler\BufferApirelioEventsHandler;
use Apirelio\Symfony\Resolver\NullApplicationResolver;
use Apirelio\Symfony\Resolver\NullCustomerResolver;
use Apirelio\Symfony\Support\RouteNormalizer;
use Apirelio\Symfony\Transport\FileBufferTransport;
use Apirelio\Symfony\Transport\HttpBatchTransport;
use Apirelio\Symfony\Transport\MessengerTransport;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class ApirelioExtension extends Extension
{
    /** @param array<int, array<string, mixed>> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration, $configs);
        if ($config['buffer_path'] === null || $config['buffer_path'] === '') {
            $cacheDirectory = $container->getParameter('kernel.cache_dir');
            if (! is_string($cacheDirectory)) {
                throw new LogicException('The kernel.cache_dir parameter must be a string.');
            }

            $config['buffer_path'] = rtrim($cacheDirectory, '/').'/apirelio/events.ndjson';
        }

        $container->register(NullCustomerResolver::class);
        $container->register(NullApplicationResolver::class);
        $container->setAlias(CustomerResolver::class, NullCustomerResolver::class);
        $container->setAlias(ApplicationResolver::class, NullApplicationResolver::class);

        $container->register(RouteNormalizer::class)
            ->setArgument('$router', new Reference(RouterInterface::class));
        $container->register(HttpBatchTransport::class)
            ->setArgument('$http', new Reference(HttpClientInterface::class))
            ->setArgument('$config', $config);
        $container->register(FileBufferTransport::class)
            ->setArgument('$http', new Reference(HttpBatchTransport::class))
            ->setArgument('$config', $config);

        $transportName = $config['transport'];
        if (! is_string($transportName)) {
            throw new LogicException('The apirelio.transport option must be a string.');
        }

        $transport = match ($transportName) {
            'sync' => HttpBatchTransport::class,
            'file_buffer' => FileBufferTransport::class,
            'messenger' => $this->registerMessengerTransport($container, (string) $config['messenger_bus']),
            default => throw new LogicException(sprintf('Unsupported Apirelio transport "%s".', $transportName)),
        };
        $container->setAlias(EventTransport::class, $transport);

        $container->register(ApirelioManager::class)
            ->setPublic(true)
            ->setArguments([
                new Reference(RequestStack::class),
                new Reference(EventTransport::class),
                new Reference(RouteNormalizer::class),
                new Reference(CustomerResolver::class),
                new Reference(ApplicationResolver::class),
                $config,
                new Reference(LoggerInterface::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ]);
        $container->setAlias('apirelio', ApirelioManager::class)->setPublic(true);

        $container->register(TrackApiRequestSubscriber::class)
            ->setArgument('$apirelio', new Reference(ApirelioManager::class))
            ->addTag('kernel.event_subscriber');
        $container->register(BufferApirelioEventsHandler::class)
            ->setArgument('$transport', new Reference(HttpBatchTransport::class))
            ->addTag('messenger.message_handler');
        $container->register(FlushBufferCommand::class)
            ->setArgument('$transport', new Reference(FileBufferTransport::class))
            ->addTag('console.command');
    }

    private function registerMessengerTransport(ContainerBuilder $container, string $bus): string
    {
        $container->register(MessengerTransport::class)
            ->setArgument('$bus', new Reference($bus));

        return MessengerTransport::class;
    }
}
