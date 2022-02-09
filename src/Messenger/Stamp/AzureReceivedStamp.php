<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Azure Service Bus receiver stamp with message metadata
 */
class AzureReceivedStamp implements NonSendableStampInterface
{
    /** @var string */
    private $message;

    /** @var string|null */
    private $locationHeader;

    /**
     * @param string $message the original message
     * @param string|null $locationHeader optional "Location" response header
     */
    public function __construct(string $message, ?string $locationHeader)
    {
        $this->message = $message;
        $this->locationHeader = $locationHeader;
    }

    /**
     * Create a stamp from an HTTP response in the receiver transport
     * @internal
     * @throws HttpExceptionInterface|TransportExceptionInterface
     */
    public static function createFromResponse(ResponseInterface $response): self
    {
        return new self(
            $response->getContent(),
            $response->getHeaders()['location'][0] ?? null
        );
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLocationHeader(): ?string
    {
        return $this->locationHeader;
    }
}
