<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Transport;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AzureHttpClient implements HttpClientInterface
{
    use DecoratorTrait;

    /** @var SasTokenGenerator */
    private $sasTokenGenerator;

    public function __construct(HttpClientInterface $client, SasTokenGenerator $sasTokenGenerator)
    {
        $this->client = $client;
        $this->sasTokenGenerator = $sasTokenGenerator;
    }

    /**
     * @param mixed[] $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        /** @var array{headers?: array<string, string>} $options */
        $options['headers'] = $options['headers'] ?? [];
        $options['headers']['Authorization'] = $this->sasTokenGenerator->generateSharedAccessSignatureToken();

        return $this->client->request($method, $url, $options);
    }
}
