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
    private const RECEIVE_MODES = [
        AzureTransport::RECEIVE_MODE_PEEK_LOCK,
        AzureTransport::RECEIVE_MODE_RECEIVE_AND_DELETE,
    ];
    private const DEFAULT_OPTIONS = [
        'entity_path' => null,
        'subscription' => null,
        'token_expiry' => 3600,
        'receive_mode' => AzureTransport::RECEIVE_MODE_PEEK_LOCK,
    ];

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
        $options = $this->validateOptions($options);
        $dsnParts = $this->dsnParser->parseDsn($dsn, $options['transport_name']);

        $senderConfiguration = $this->httpClientConfigurationBuilder->buildSenderConfiguration($dsnParts, $options);
        $receiverConfiguration = $this->httpClientConfigurationBuilder->buildReceiverConfiguration($dsnParts, $options);

        return new AzureTransport(
            $serializer,
            $this->httpClientFactory->createClient($senderConfiguration),
            $this->httpClientFactory->createClient($receiverConfiguration),
            $options['receive_mode'],
            $options['entity_path'],
            $options['subscription']
        );
    }

    /**
     * Validate options and set default values
     * @param mixed[] $options
     * @return array{
     *     transport_name: string,
     *     entity_path: string,
     *     subscription: string|null,
     *     token_expiry: int,
     *     receive_mode: string
     * }
     */
    private function validateOptions(array $options): array
    {
        // Set default values
        /**
         * @var array{
         *     transport_name: string,
         *     entity_path: string|null,
         *     subscription: string|null,
         *     token_expiry: int,
         *     receive_mode: string
         * } $options
         */
        $options = array_merge(self::DEFAULT_OPTIONS, $options);

        // Missing topic or queue name
        if (null === $options['entity_path']) {
            throw new \InvalidArgumentException(
                sprintf('Missing entity_path (queue or topic) for the "%s" transport.', $options['transport_name']),
                1643989596
            );
        }

        // Invalid receive mode
        if (false === in_array($options['receive_mode'], self::RECEIVE_MODES, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid "%s" receive_mode for the "%s" transport. It must be one of: %s.',
                    $options['receive_mode'],
                    $options['transport_name'],
                    implode(', ', self::RECEIVE_MODES)
                ),
                1643994036
            );
        }

        return $options;
    }
}
