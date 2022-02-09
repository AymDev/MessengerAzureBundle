<?php

declare(strict_types=1);

namespace Tests\AymDev\MessengerAzureBundle\DependencyInjection;

use AymDev\MessengerAzureBundle\DependencyInjection\AymDevMessengerAzureExtension;
use AymDev\MessengerAzureBundle\Messenger\Transport\AzureHttpClientConfigurationBuilder;
use AymDev\MessengerAzureBundle\Messenger\Transport\AzureTransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AymDevMessengerAzureExtensionTest extends TestCase
{
    /**
     * The transport factory and other services must be registered and functional
     */
    public function testServiceRegistration(): void
    {
        $container = new ContainerBuilder();
        $extension = new AymDevMessengerAzureExtension();

        $extension->load([], $container);

        // HttpClient configuration builder
        self::assertInstanceOf(
            AzureHttpClientConfigurationBuilder::class,
            $container->get('aymdev_azure_service_bus.http_config_builder')
        );

        // Transport factory
        $transportFactoryDefinition = $container->getDefinition('aymdev_azure_service_bus.transport_factory');
        self::assertTrue($transportFactoryDefinition->hasTag('messenger.transport_factory'));

        self::assertInstanceOf(
            AzureTransportFactory::class,
            $container->get('aymdev_azure_service_bus.transport_factory')
        );
    }
}
