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
     * @param array{
     *     transport_name: string,
     *     entity_path: string,
     *     subscription: string|null,
     *     token_expiry: int,
     *     receive_mode: string
     * } $options
     * @return array{endpoint: string, options: mixed[]}
     */
    public function buildSenderConfiguration(string $dsn, array $options): array
    {
        return $this->buildConfiguration(false, $dsn, $options);
    }

    /**
     * Build configuration for a receiver HttpClient transport
     * @param array{
     *     transport_name: string,
     *     entity_path: string,
     *     subscription: string|null,
     *     token_expiry: int,
     *     receive_mode: string
     * } $options
     * @return array{endpoint: string, options: mixed[]}
     */
    public function buildReceiverConfiguration(string $dsn, array $options): array
    {
        return $this->buildConfiguration(true, $dsn, $options);
    }

    /**
     * @param array{
     *     transport_name: string,
     *     entity_path: string,
     *     subscription: string|null,
     *     token_expiry: int,
     *     receive_mode: string
     * } $options
     * @return array{endpoint: string, options: mixed[]}
     */
    private function buildConfiguration(bool $isReceiver, string $dsn, array $options): array
    {
        [$sharedAccessKeyName, $sharedAccessKey, $namespace] = $this->parseDsn($dsn, $options['transport_name']);
        $endpoint = $this->getBaseEndpoint($isReceiver, $namespace, $options['entity_path'], $options['subscription']);

        $sasToken = $this->generateSharedAccessSignatureToken(
            $endpoint,
            $sharedAccessKeyName,
            $sharedAccessKey,
            $options['token_expiry']
        );

        $clientOptions = [
            'endpoint' => $endpoint,
            'options' => [
                'headers' => [
                    'Authorization' => $sasToken,
                ],
            ]
        ];

        if (!$isReceiver) {
            $clientOptions['options']['headers']['Content-Type'] = 'application/atom+xml;type=entry;charset=utf-8';
        }

        return $clientOptions;
    }

    /**
     * Parse the DSN to extract the namespace and shared access key
     * @return string[]
     */
    private function parseDsn(string $dsn, string $transportName): array
    {
        if (1 !== preg_match('~^azure://(.+):(.+)@(.+)$~', $dsn, $matches)) {
            $message = sprintf('Invalid Azure Service Bus DSN for the "%s" transport. ', $transportName);
            $message .= 'It must be in the following format: azure://SharedAccessKeyName:SharedAccessKey@namespace';
            throw new \InvalidArgumentException($message, 1643988474);
        }

        array_shift($matches);
        return $matches;
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

    /**
     * Generate the SAS token used to authenticate on Azure Service Bus REST API.
     */
    private function generateSharedAccessSignatureToken(
        string $endpoint,
        string $accessKeyName,
        string $accessKey,
        int $tokenExpiry
    ): string {
        // Token expiry instant
        $expiry = time() + $tokenExpiry;

        // URL-encoded URI of the resource being accessed
        $resource = strtolower(rawurlencode(strtolower($endpoint)));

        // URL-encoded HMAC SHA256 signature
        $toSign = $resource . "\n" . $expiry;
        $signature = rawurlencode(base64_encode(hash_hmac('sha256', $toSign, $accessKey, true)));

        return sprintf(
            'SharedAccessSignature sig=%s&se=%d&skn=%s&sr=%s',
            $signature,
            $expiry,
            $accessKeyName,
            $resource
        );
    }
}
