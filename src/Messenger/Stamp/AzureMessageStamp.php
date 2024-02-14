<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Azure Service Bus stamp added to sent and received messages containing message metadata
 */
class AzureMessageStamp implements StampInterface
{
    /**
     * @internal
     * @param string $message the original message
     * @param string|null $locationHeader optional "Location" response header
     */
    public function __construct(
        private readonly string $entityPath,
        private readonly string $message,
        private readonly ?string $subscriptionName = null,
        private readonly ?string $locationHeader = null
    ) {
    }

    /**
     * Create a stamp from an HTTP response in the receiver transport
     * @internal
     * @throws HttpExceptionInterface|TransportExceptionInterface
     */
    public static function createFromResponse(
        ResponseInterface $response,
        string $entityPath,
        ?string $subscriptionName
    ): self {
        return new self(
            $entityPath,
            $response->getContent(),
            $subscriptionName,
            $response->getHeaders()['location'][0] ?? null
        );
    }

    public function getEntityPath(): string
    {
        return $this->entityPath;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSubscriptionName(): ?string
    {
        return $this->subscriptionName;
    }

    public function getLocationHeader(): ?string
    {
        return $this->locationHeader;
    }
}
