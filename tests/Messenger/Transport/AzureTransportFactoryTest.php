<?php

declare(strict_types=1);

namespace Tests\AymDev\MessengerAzureBundle\Messenger\Transport;

use AymDev\MessengerAzureBundle\Messenger\Transport\AzureHttpClientConfigurationBuilder;
use AymDev\MessengerAzureBundle\Messenger\Transport\AzureHttpClientFactory;
use AymDev\MessengerAzureBundle\Messenger\Transport\AzureTransport;
use AymDev\MessengerAzureBundle\Messenger\Transport\AzureTransportFactory;
use AymDev\MessengerAzureBundle\Messenger\Transport\DsnParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class AzureTransportFactoryTest extends TestCase
{
    /**
     * The factory must supports DSN prefixed with "azure://"
     */
    public function testSupportsAzureDsn(): void
    {
        $factory = new AzureTransportFactory(
            new DsnParser(),
            new AzureHttpClientConfigurationBuilder(),
            new AzureHttpClientFactory()
        );

        self::assertTrue($factory->supports('azure://KeyName:Key@namespace', []));
        self::assertTrue($factory->supports('azure://', []));
        self::assertFalse($factory->supports('test://something', []));
    }

    /**
     * The entity path is mandatory
     */
    public function testThrowsOnMissingEntityPath(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionCode(1643989596);

        $factory = new AzureTransportFactory(
            new DsnParser(),
            new AzureHttpClientConfigurationBuilder(),
            new AzureHttpClientFactory()
        );

        $factory->createTransport(
            'azure://KeyName:Key@namespace',
            [
                'transport_name' => 'test-transport',
            ],
            self::createMock(SerializerInterface::class)
        );
    }

    /**
     * The receive mode can only be peek-lock or receive-and-delete
     */
    public function testThrowsOnInvalidReceiveMode(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionCode(1643994036);

        $factory = new AzureTransportFactory(
            new DsnParser(),
            new AzureHttpClientConfigurationBuilder(),
            new AzureHttpClientFactory()
        );

        $factory->createTransport(
            'azure://KeyName:Key@namespace',
            [
                'transport_name' => 'test-transport',
                'entity_path' => 'entity',
                'receive_mode' => 'invalid',
            ],
            self::createMock(SerializerInterface::class)
        );
    }
}
