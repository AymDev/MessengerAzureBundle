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
        $dsnParts = [
            'shared_access_key_name' => 'KeyName',
            'shared_access_key' => 'Key',
            'namespace' => 'namespace',
        ];
        $options = [
            'entity_path' => 'entity',
            'subscription' => null,
            'token_expiry' => 3600,
        ];

        $factory = new AzureHttpClientConfigurationBuilder();

        return [
            [true, $factory->buildSenderConfiguration($dsnParts, $options)],
            [false, $factory->buildReceiverConfiguration($dsnParts, $options)],
        ];
    }

    /**
     * The endpoint for a receiver client for a topic must include the subscription
     */
    public function testTopicReceiverConfiguration(): void
    {
        $factory = new AzureHttpClientConfigurationBuilder();
        $configuration = $factory->buildReceiverConfiguration([
            'shared_access_key_name' => 'KeyName',
            'shared_access_key' => 'Key',
            'namespace' => 'namespace',
        ], [
            'entity_path' => 'entity',
            'subscription' => 'subscription',
            'token_expiry' => 3600,
        ]);

        // It MUST end with a trailing slash
        self::assertSame(
            'https://namespace.servicebus.windows.net/entity/subscriptions/subscription/',
            $configuration['endpoint']
        );
    }
}
