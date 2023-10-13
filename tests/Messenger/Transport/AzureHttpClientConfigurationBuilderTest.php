<?php

declare(strict_types=1);

namespace Tests\AymDev\MessengerAzureBundle\Messenger\Transport;

use AymDev\MessengerAzureBundle\Messenger\Transport\AzureHttpClientConfigurationBuilder;
use AymDev\MessengerAzureBundle\Messenger\Transport\AzureTransport;
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
        self::assertArrayHasKey('shared_access_key_name', $configuration);
        self::assertArrayHasKey('shared_access_key', $configuration);
        self::assertArrayHasKey('token_expiry', $configuration);
        self::assertArrayHasKey('options', $configuration);

        // It MUST end with a trailing slash
        self::assertStringEndsWith('/', $configuration['endpoint']);
        self::assertSame('https://namespace.servicebus.windows.net/entity/', $configuration['endpoint']);

        self::assertSame('KeyName', $configuration['shared_access_key_name']);
        self::assertSame('Key', $configuration['shared_access_key']);
        self::assertSame(3600, $configuration['token_expiry']);

        if ($isSender) {
            self::assertArrayHasKey('Content-Type', $configuration['options']['headers']);
        }
    }

    /**
     * @return mixed[][]
     */
    public function provideClientConfiguration(): array
    {
        $options = [
            'shared_access_key_name' => 'KeyName',
            'shared_access_key' => 'Key',
            'namespace' => 'namespace',
            'entity_path' => 'entity',
            'subscription' => null,
            'token_expiry' => 3600,
            'receive_mode' => AzureTransport::RECEIVE_MODE_PEEK_LOCK,
        ];

        $factory = new AzureHttpClientConfigurationBuilder();

        return [
            [true, $factory->buildSenderConfiguration($options)],
            [false, $factory->buildReceiverConfiguration($options)],
        ];
    }

    /**
     * The endpoint for a receiver client for a topic must include the subscription
     */
    public function testTopicReceiverConfiguration(): void
    {
        $options = [
            'shared_access_key_name' => 'KeyName',
            'shared_access_key' => 'Key',
            'namespace' => 'namespace',
            'entity_path' => 'entity',
            'subscription' => 'subscription',
            'token_expiry' => 3600,
            'receive_mode' => AzureTransport::RECEIVE_MODE_PEEK_LOCK,
        ];

        $factory = new AzureHttpClientConfigurationBuilder();
        $configuration = $factory->buildReceiverConfiguration($options);

        // It MUST end with a trailing slash
        self::assertSame(
            'https://namespace.servicebus.windows.net/entity/subscriptions/subscription/',
            $configuration['endpoint']
        );
    }
}
