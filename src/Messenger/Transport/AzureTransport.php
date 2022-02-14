<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Transport;

use AymDev\MessengerAzureBundle\Messenger\Stamp\AzureBrokerPropertiesStamp;
use AymDev\MessengerAzureBundle\Messenger\Stamp\AzureMessageStamp;
use AymDev\MessengerAzureBundle\Messenger\Stamp\AzureReceivedStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Messenger transport for Azure Service Bus
 * @internal
 */
final class AzureTransport implements TransportInterface
{
    public const RECEIVE_MODE_PEEK_LOCK = 'peek-lock';
    public const RECEIVE_MODE_RECEIVE_AND_DELETE = 'receive-and-delete';

    /** @var SerializerInterface */
    private $serializer;

    /** @var HttpClientInterface */
    private $senderClient;

    /** @var HttpClientInterface */
    private $receiverClient;

    /** @var string */
    private $receiveMode;

    /** @var string */
    private $entityPath;

    /** @var string|null */
    private $subscriptionName;

    public function __construct(
        SerializerInterface $serializer,
        HttpClientInterface $senderClient,
        HttpClientInterface $receiverClient,
        string $receiveMode,
        string $entityPath,
        ?string $subscriptionName = null
    ) {
        $this->serializer = $serializer;
        $this->senderClient = $senderClient;
        $this->receiverClient = $receiverClient;
        $this->receiveMode = $receiveMode;
        $this->entityPath = $entityPath;
        $this->subscriptionName = $subscriptionName;
    }

    /**
     * @inheritdoc
     */
    public function get(): iterable
    {
        $method = $this->receiveMode === self::RECEIVE_MODE_PEEK_LOCK ? 'POST' : 'DELETE';
        $expectedStatusCode = $this->receiveMode === self::RECEIVE_MODE_PEEK_LOCK ? 201 : 200;

        // HTTP call
        try {
            $response = $this->receiverClient->request($method, 'messages/head');

            $statusCode = $response->getStatusCode();
            $body = $response->getContent();
            $headers = $response->getHeaders();
        } catch (TransportExceptionInterface | HttpExceptionInterface $e) {
            throw new TransportException(
                sprintf('Could not get message from Azure Service Bus: %s', $e->getMessage()),
                1644315123,
                $e
            );
        }

        if (204 === $statusCode) {
            return [];
        } elseif ($expectedStatusCode !== $statusCode) {
            throw new TransportException(
                sprintf('Unexpected status code "%d" from Azure Service Bus', $statusCode),
                1644315645
            );
        }

        // Decode message
        $envelope = $this->serializer->decode([
            'body' => $body,
            'headers' => $headers,
        ]);

        // Add stamps
        $brokerPropertiesStamp = AzureBrokerPropertiesStamp::createFromResponse($response);
        $envelope = $envelope
            ->with(AzureReceivedStamp::createFromResponse($response))
            ->with(AzureMessageStamp::createFromResponse($response, $this->entityPath, $this->subscriptionName))
            ->with($brokerPropertiesStamp)
        ;

        // Set message ID stamp
        if (null !== $brokerPropertiesStamp->getMessageId()) {
            $envelope = $envelope->with(new TransportMessageIdStamp($brokerPropertiesStamp->getMessageId()));
        }

        return [$envelope];
    }

    /**
     * @inheritdoc
     */
    public function ack(Envelope $envelope): void
    {
        $this->delete($envelope);
    }

    /**
     * @inheritdoc
     */
    public function reject(Envelope $envelope): void
    {
        $this->delete($envelope);
    }

    public function send(Envelope $envelope): Envelope
    {
        $additionalHeaders = [];

        // Build broker properties
        /** @var null|AzureBrokerPropertiesStamp $brokerProperties */
        $brokerProperties = $envelope->last(AzureBrokerPropertiesStamp::class);
        if (null !== $brokerProperties) {
            try {
                $additionalHeaders['BrokerProperties'] = $brokerProperties->encode();
            } catch (\JsonException $e) {
                throw new TransportException('Could not encode the "BrokerProperties" header.', 1644511135, $e);
            }
        }

        // Decode message
        $encodedMessage = $this->serializer->encode($envelope);
        if (!isset($encodedMessage['body'])) {
            throw new \LogicException('Missing encoded message body.', 1644403794);
        }
        if (isset($encodedMessage['headers'])) {
            $additionalHeaders = array_merge($additionalHeaders, $encodedMessage['headers']);
        }

        // HTTP call
        try {
            $this->senderClient->request('POST', 'messages', [
                'body' => $encodedMessage['body'],
                'headers' => $additionalHeaders,
            ]);
        } catch (TransportExceptionInterface | HttpExceptionInterface $e) {
            throw new TransportException(
                sprintf('Could not send message to Azure Service Bus: %s', $e->getMessage()),
                1644415901,
                $e
            );
        }

        return $envelope->with(new AzureMessageStamp(
            $this->entityPath,
            $encodedMessage['body'],
            $this->subscriptionName
        ));
    }

    /**
     * Delete a message (only when the receive mode is set to "Peek Lock")
     * @throws \LogicException|TransportException
     */
    private function delete(Envelope $envelope): void
    {
        // Messages are already deleted in the "Receive And Delete" receive mode
        if (self::RECEIVE_MODE_RECEIVE_AND_DELETE === $this->receiveMode) {
            return;
        }

        try {
            $this->receiverClient->request('DELETE', $this->getDeleteUri($envelope));
        } catch (TransportExceptionInterface | HttpExceptionInterface $e) {
            throw new TransportException(
                sprintf('Could not delete message from Azure Service Bus: %s', $e->getMessage()),
                1644340210,
                $e
            );
        }
    }

    /**
     * Get or build the URI/URL to use to delete a message
     * @throws \LogicException
     */
    private function getDeleteUri(Envelope $envelope): string
    {
        // Use delete location URL
        /** @var null|AzureMessageStamp $receivedStamp */
        $receivedStamp = $envelope->last(AzureMessageStamp::class);

        if (null !== $receivedStamp && null !== $receivedStamp->getLocationHeader()) {
            return $receivedStamp->getLocationHeader();
        }

        // Build URI using Broker Properties
        /** @var null|AzureBrokerPropertiesStamp $brokerPropertiesStamp */
        $brokerPropertiesStamp = $envelope->last(AzureBrokerPropertiesStamp::class);

        if (null === $brokerPropertiesStamp) {
            throw new \LogicException(
                sprintf(
                    'Cannot delete message: missing "%s" stamp on the envelope.',
                    AzureBrokerPropertiesStamp::class
                ),
                1644340687
            );
        }

        $messageIdentifier = $brokerPropertiesStamp->getMessageId() ?? $brokerPropertiesStamp->getSequenceNumber();
        if (null === $messageIdentifier) {
            throw new \LogicException(
                'Cannot delete message: missing "MessageId" or "SequenceNumber" in the BrokerProperties.',
                1644340921
            );
        }

        $lockToken = $brokerPropertiesStamp->getLockToken();
        if (null === $lockToken) {
            throw new \LogicException(
                'Cannot delete message: missing "LockToken" in the BrokerProperties.',
                1644340926
            );
        }

        return sprintf('messages/%s/%s', $messageIdentifier, $lockToken);
    }
}
