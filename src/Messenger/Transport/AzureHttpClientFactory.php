<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Transport;

use Symfony\Component\HttpClient\HttpClient;

class AzureHttpClientFactory
{
    /**
     * @param array{
     *     endpoint: string,
     *     shared_access_key_name: string,
     *     shared_access_key: string,
     *     token_expiry: int,
     *     options: mixed[]
     * } $options
     * @return AzureHttpClient
     */
    public function createClient(array $options): AzureHttpClient
    {
        $httpClient = HttpClient::createForBaseUri(
            $options['endpoint'],
            $options['options'],
        );

        $sasTokenGenerator = new SasTokenGenerator(
            $options['endpoint'],
            $options['shared_access_key_name'],
            $options['shared_access_key'],
            $options['token_expiry']
        );

        return new AzureHttpClient($sasTokenGenerator, $httpClient);
    }
}
