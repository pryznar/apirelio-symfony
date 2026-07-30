<?php

declare(strict_types=1);

namespace Apirelio\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('apirelio');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $children = $rootNode->children();
        $children->booleanNode('enabled')->defaultTrue();
        $children->scalarNode('endpoint')->defaultValue('https://api.apirelio.com')->cannotBeEmpty();
        $children->scalarNode('api_key')->defaultValue('');
        $children->scalarNode('service')->defaultValue('symfony')->cannotBeEmpty();
        $children->scalarNode('environment')->defaultValue('production')->cannotBeEmpty();
        $children->scalarNode('release')->defaultNull();
        $children->enumNode('transport')->values(['messenger', 'sync', 'file_buffer'])->defaultValue('messenger');
        $children->scalarNode('messenger_bus')->defaultValue('messenger.default_bus')->cannotBeEmpty();
        $paths = $children->arrayNode('paths');
        $paths->scalarPrototype();
        $paths->defaultValue(['/api/*'])->requiresAtLeastOneElement();
        $children->floatNode('timeout_seconds')->min(0.1)->defaultValue(2.0);
        $children->floatNode('connect_timeout_seconds')->min(0.1)->defaultValue(0.5);
        $children->integerNode('batch_size')->min(1)->max(500)->defaultValue(500);
        $children->integerNode('flush_interval_seconds')->min(1)->defaultValue(10);
        $children->scalarNode('buffer_path')->defaultNull();
        $children->scalarNode('error_code_json_path')->defaultValue('error.code')->cannotBeEmpty();
        $captureHeaders = $children->arrayNode('capture_headers');
        $captureHeaders->scalarPrototype();
        $captureHeaders->defaultValue(['x-api-version', 'x-sdk-version', 'user-agent']);
        $metadataKeys = $children->arrayNode('metadata_keys');
        $metadataKeys->scalarPrototype();
        $metadataKeys->defaultValue([]);

        return $treeBuilder;
    }
}
