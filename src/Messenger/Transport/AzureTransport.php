<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
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

    public function __construct(
        SerializerInterface $serializer,
        HttpClientInterface $senderClient,
        HttpClientInterface $receiverClient,
        string $receiveMode
    ) {
        $this->serializer = $serializer;
        $this->senderClient = $senderClient;
        $this->receiverClient = $receiverClient;
        $this->receiveMode = $receiveMode;
    }

    public function get(): iterable
    {
        // TODO: Implement get() method.
    }

    public function ack(Envelope $envelope): void
    {
        // TODO: Implement ack() method.
    }

    public function reject(Envelope $envelope): void
    {
        // TODO: Implement reject() method.
    }

    public function send(Envelope $envelope): Envelope
    {
        // TODO: Implement send() method.
    }
}
