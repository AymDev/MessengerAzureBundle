<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\DependencyInjection;

use AymDev\MessengerAzureBundle\Messenger\Transport\AzureHttpClientConfigurationBuilder;
use AymDev\MessengerAzureBundle\Messenger\Transport\AzureTransportFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class AymDevMessengerAzureExtension extends Extension
{
    private const SERVICE_PREFIX = 'aymdev_azure_service_bus';

    /**
     * @param mixed[] $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // HttpClient configuration builder
        $httpConfigBuilderId = self::SERVICE_PREFIX . '.http_config_builder';
        $httpConfigBuilderDefinition = new Definition(AzureHttpClientConfigurationBuilder::class);
        $container
            ->setDefinition($httpConfigBuilderId, $httpConfigBuilderDefinition)
            ->setPublic(false)
        ;

        // Transport factory
        $container
            ->setDefinition(self::SERVICE_PREFIX . '.transport_factory', new Definition(AzureTransportFactory::class))
            ->addArgument(new Reference($httpConfigBuilderId))
            ->addTag('messenger.transport_factory')
            ->setPublic(false)
        ;
    }
}
