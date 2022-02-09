<?php

declare(strict_types=1);

namespace Tests\AymDev\MessengerAzureBundle\Messenger\Stamp;

use AymDev\MessengerAzureBundle\Messenger\Stamp\AzureReceivedStamp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AzureReceivedStampTest extends TestCase
{
    /**
     * Body and "Location" header must be kept in the stamp when created from a response
     */
    public function testCreateFromResponse(): void
    {
        $body = 'test-body';
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
        $stamp = AzureReceivedStamp::createFromResponse($firstReponse);

        self::assertSame($body, $stamp->getMessage());
        self::assertNull($stamp->getLocationHeader());

        $secondResponse = $httpClient->request('TEST', '/');
        $stamp = AzureReceivedStamp::createFromResponse($secondResponse);

        self::assertSame($body, $stamp->getMessage());
        self::assertSame($locationHeader, $stamp->getLocationHeader());
    }
}
