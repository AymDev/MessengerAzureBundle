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
    /** @var string */
    private $entityPath;

    /** @var string */
    private $message;

    /** @var null|string */
    private $subscriptionName;

    /** @var string|null */
    private $locationHeader;

    /**
     * @internal
     * @param string $message the original message
     * @param string|null $locationHeader optional "Location" response header
     */
    public function __construct(
        string $entityPath,
        string $message,
        ?string $subscriptionName = null,
        ?string $locationHeader = null
    ) {
        $this->entityPath = $entityPath;
        $this->message = $message;
        $this->subscriptionName = $subscriptionName;
        $this->locationHeader = $locationHeader;
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
