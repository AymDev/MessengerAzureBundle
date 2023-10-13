<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Transport;

use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Messenger transport factory for Azure Service Bus
 * @internal
 */
final class AzureTransportFactory implements TransportFactoryInterface
{
    /** @var DsnParser */
    private $dsnParser;

    /** @var AzureHttpClientConfigurationBuilder */
    private $httpClientConfigurationBuilder;

    /** @var AzureHttpClientFactory */
    private $httpClientFactory;

    public function __construct(
        DsnParser $dsnParser,
        AzureHttpClientConfigurationBuilder $httpClientConfigurationBuilder,
        AzureHttpClientFactory $httpClientFactory
    ) {
        $this->dsnParser = $dsnParser;
        $this->httpClientConfigurationBuilder = $httpClientConfigurationBuilder;
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * @param mixed[] $options
     */
    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'azure://');
    }

    /**
     * @param mixed[] $options
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $transportName = $options['transport_name'];
        if (!is_string($transportName)) {
            throw new \InvalidArgumentException('The "transport_name" option must be set.');
        }
        unset($options['transport_name']);

        $options = $this->dsnParser->parseDsn($dsn, $options, $transportName);

        $senderConfiguration = $this->httpClientConfigurationBuilder->buildSenderConfiguration($options);
        $receiverConfiguration = $this->httpClientConfigurationBuilder->buildReceiverConfiguration($options);

        return new AzureTransport(
            $serializer,
            $this->httpClientFactory->createClient($senderConfiguration),
            $this->httpClientFactory->createClient($receiverConfiguration),
            $options['receive_mode'],
            $options['entity_path'],
            $options['subscription']
        );
    }
}
