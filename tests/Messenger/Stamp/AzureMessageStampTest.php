<?php

declare(strict_types=1);

namespace Tests\AymDev\MessengerAzureBundle\Messenger\Stamp;

use AymDev\MessengerAzureBundle\Messenger\Stamp\AzureMessageStamp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AzureMessageStampTest extends TestCase
{
    /**
     * Entity path, response body, subscription name and "Location" header must be kept in the stamp when created from a
     * response
     */
    public function testCreateFromResponse(): void
    {
        $entityPath = 'test-entity';
        $body = 'test-body';
        $subscriptionName = 'test-subscription';
        $locationHeader = 'test-location';

        $httpClient = new MockHttpClient([
            new MockResponse($body),
            new MockResponse($body, [
                'response_headers' => [
                    'Location' => $locationHeader,
                ],
            ])
        ]);

        $firstReponse = $httpClient->request('TEST', '/');
        $stamp = AzureMessageStamp::createFromResponse($firstReponse, $entityPath, null);

        self::assertSame($entityPath, $stamp->getEntityPath());
        self::assertSame($body, $stamp->getMessage());
        self::assertNull($stamp->getSubscriptionName());
        self::assertNull($stamp->getLocationHeader());

        $secondResponse = $httpClient->request('TEST', '/');
        $stamp = AzureMessageStamp::createFromResponse($secondResponse, $entityPath, $subscriptionName);

        self::assertSame($entityPath, $stamp->getEntityPath());
        self::assertSame($body, $stamp->getMessage());
        self::assertSame($subscriptionName, $stamp->getSubscriptionName());
        self::assertSame($locationHeader, $stamp->getLocationHeader());
    }
}
