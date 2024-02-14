<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Exception;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Throwable;

/**
 * This exception class contains an envelope for a message that could not be decoded. Its only purpose is to provide the
 * same metadata as a decoded messages with its stamps.
 * It is thrown when a consumer serializer throws a MessageDecodingFailedException and can be used for logging using the
 * console.error Symfony event.
 */
final class SerializerDecodingException extends MessageDecodingFailedException
{
    private Envelope $envelope;

    /**
     * @param Envelope $envelope an envelope with an empty message
     */
    public function __construct(Envelope $envelope, string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->envelope = $envelope;
    }

    public function getEnvelope(): Envelope
    {
        return $this->envelope;
    }
}
