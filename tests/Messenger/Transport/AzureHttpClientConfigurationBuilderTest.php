<?php

declare(strict_types=1);

namespace Tests\AymDev\MessengerAzureBundle\Messenger\Transport;

use AymDev\MessengerAzureBundle\Messenger\Transport\AzureHttpClientConfigurationBuilder;
use PHPUnit\Framework\TestCase;

final class AzureHttpClientConfigurationBuilderTest extends TestCase
{
    /**
     * Check default configurations
     * @dataProvider provideClientConfiguration
     * @param mixed[] $configuration
     */
    public function testDefaultConfiguration(bool $isSender, array $configuration): void
    {
        self::assertArrayHasKey('endpoint', $configuration);
        self::assertArrayHasKey('options', $configuration);

        // It MUST end with a trailing slash
        self::assertStringEndsWith('/', $configuration['endpoint']);
        self::assertSame('https://namespace.servicebus.windows.net/entity/', $configuration['endpoint']);

        if ($isSender) {
            self::assertArrayHasKey('Content-Type', $configuration['options']['headers']);
        }

        self::assertArrayHasKey('headers', $configuration['options']);
        self::assertArrayHasKey('Authorization', $configuration['options']['headers']);
        self::assertStringStartsWith(
            'SharedAccessSignature ',
            $configuration['options']['headers']['Authorization']
        );
    }

    /**
     * @return mixed[][]
     */
    public function provideClientConfiguration(): array
    {
        $dsn = 'azure://KeyName:Key@namespace';
        $options = [
            'transport_name' => 'test-transport',
            'entity_path' => 'entity',
            'subscription' => null,
            'token_expiry' => 3600,
            'receive_mode' => 'peek-lock',
        ];

        $factory = new AzureHttpClientConfigurationBuilder();

        return [
            [true, $factory->buildSenderConfiguration($dsn, $options)],
            [false, $factory->buildReceiverConfiguration($dsn, $options)],
        ];
    }

    /**
     * The endpoint for a receiver client for a topic must include the subscription
     */
    public function testTopicReceiverConfiguration(): void
    {
        $factory = new AzureHttpClientConfigurationBuilder();
        $configuration = $factory->buildReceiverConfiguration('azure://KeyName:Key@namespace', [
            'transport_name' => 'test-transport',
            'entity_path' => 'entity',
            'subscription' => 'subscription',
            'token_expiry' => 3600,
            'receive_mode' => 'peek-lock',
        ]);

        // It MUST end with a trailing slash
        self::assertSame(
            'https://namespace.servicebus.windows.net/entity/subscriptions/subscription/',
            $configuration['endpoint']
        );
    }

    /**
     * The DSN must respect a specific format
     */
    public function testThrowsOnInvalidDsn(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionCode(1643988474);

        $factory = new AzureHttpClientConfigurationBuilder();
        $factory->buildSenderConfiguration('azure://invalid-dsn', [
            'transport_name' => 'test-transport',
            'entity_path' => 'entity',
            'token_expiry' => 3600,
            'receive_mode' => 'peek-lock',
            'subscription' => null,
        ]);
    }
}
