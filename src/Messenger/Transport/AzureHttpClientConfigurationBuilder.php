<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Transport;

/**
 * Configuration builder for the Azure transports HTTP clients
 * @internal
 */
final class AzureHttpClientConfigurationBuilder
{
    /**
     * Build configuration for a sender HttpClient transport
     *
     * @param array{
     *     shared_access_key_name: string,
     *     shared_access_key: string,
     *     namespace: string,
     *     entity_path: string,
     *     subscription: string|null,
     *     token_expiry: int,
     *     receive_mode: AzureTransport::RECEIVE_MODE_*,
     * } $options
     * @return array{
     *     endpoint: string,
     *     shared_access_key_name: string,
     *     shared_access_key: string,
     *     token_expiry: int,
     *     options: array{headers: array<string, string>},
     * }
     */
    public function buildSenderConfiguration(array $options): array
    {
        return $this->buildConfiguration(false, $options);
    }

    /**
     * Build configuration for a receiver HttpClient transport
     *
     * @param array{
     *     shared_access_key_name: string,
     *     shared_access_key: string,
     *     namespace: string,
     *     entity_path: string,
     *     subscription: string|null,
     *     token_expiry: int,
     *     receive_mode: AzureTransport::RECEIVE_MODE_*,
     * } $options
     * @return array{
     *     endpoint: string,
     *     shared_access_key_name: string,
     *     shared_access_key: string,
     *     token_expiry: int,
     *     options: array{headers: array<string, string>},
     * }
     */
    public function buildReceiverConfiguration(array $options): array
    {
        return $this->buildConfiguration(true, $options);
    }

    /**
     * @param array{
     *     shared_access_key_name: string,
     *     shared_access_key: string,
     *     namespace: string,
     *     entity_path: string,
     *     subscription: string|null,
     *     token_expiry: int,
     *     receive_mode: AzureTransport::RECEIVE_MODE_*,
     * } $options
     * @return array{
     *     endpoint: string,
     *     shared_access_key_name: string,
     *     shared_access_key: string,
     *     token_expiry: int,
     *     options: array{headers: array<string, string>},
     * }
     */
    private function buildConfiguration(bool $isReceiver, array $options): array
    {
        $endpoint = $this->getBaseEndpoint(
            $isReceiver,
            $options['namespace'],
            $options['entity_path'],
            $options['subscription']
        );

        $clientOptions = [
            'endpoint' => $endpoint,
            'shared_access_key_name' => $options['shared_access_key_name'],
            'shared_access_key' => $options['shared_access_key'],
            'token_expiry' => $options['token_expiry'],
            'options' => [
                'headers' => [],
            ]
        ];

        if (!$isReceiver) {
            $clientOptions['options']['headers']['Content-Type'] = 'application/atom+xml;type=entry;charset=utf-8';
        }

        return $clientOptions;
    }

    /**
     * Build the base endpoint URL used by HTTP client and SAS token
     */
    private function getBaseEndpoint(
        bool $isReceiver,
        string $namespace,
        string $entityPath,
        ?string $subscription
    ): string {
        // Endpoint for a topic receiver transport
        if ($isReceiver && null !== $subscription) {
            return sprintf(
                'https://%s.servicebus.windows.net/%s/subscriptions/%s/',
                $namespace,
                $entityPath,
                $subscription
            );
        }

        return sprintf('https://%s.servicebus.windows.net/%s/', $namespace, $entityPath);
    }
}
