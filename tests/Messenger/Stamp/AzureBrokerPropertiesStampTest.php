<?php

declare(strict_types=1);

namespace Tests\AymDev\MessengerAzureBundle\Messenger\Stamp;

use AymDev\MessengerAzureBundle\Messenger\Stamp\AzureBrokerPropertiesStamp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class AzureBrokerPropertiesStampTest extends TestCase
{
    /**
     * The "BrokerProperties" header and its properties must be optional
     * @dataProvider provideMissingBrokerPropertiesResponses
     */
    public function testCreateFromResponseWithMissingProperties(MockResponse $mockResponse): void
    {
        $httpClient = new MockHttpClient([$mockResponse]);

        $response = $httpClient->request('TEST', '/');
        $stamp = AzureBrokerPropertiesStamp::createFromResponse($response);

        self::assertNull($stamp->getContentType());
        self::assertNull($stamp->getCorrelationId());
        self::assertNull($stamp->getSessionID());
        self::assertNull($stamp->getDeliveryCount());
        self::assertNull($stamp->getLockedUntilUtc());
        self::assertNull($stamp->getLockToken());
        self::assertNull($stamp->getMessageId());
        self::assertNull($stamp->getLabel());
        self::assertNull($stamp->getReplyTo());
        self::assertNull($stamp->getEnqueuedTimeUtc());
        self::assertNull($stamp->getSequenceNumber());
        self::assertNull($stamp->getTimeToLive());
        self::assertNull($stamp->getTo());
        self::assertNull($stamp->getScheduledEnqueueTimeUtc());
        self::assertNull($stamp->getReplyToSessionId());
        self::assertNull($stamp->getPartitionKey());
    }

    /**
     * @return MockResponse[][]
     */
    public function provideMissingBrokerPropertiesResponses(): array
    {
        return [
            [
                new MockResponse(''),
            ],
            [
                new MockResponse('', [
                    'response_headers' => [
                        'BrokerProperties' => '{}',
                    ],
                ]),
            ],
            [
                new MockResponse('', [
                    'response_headers' => [
                        'BrokerProperties' => json_encode([
                            'ContentType' => null,
                            'CorrelationId' => null,
                            'SessionID' => null,
                            'DeliveryCount' => null,
                            'LockedUntilUtc' => null,
                            'LockToken' => null,
                            'MessageId' => null,
                            'Label' => null,
                            'ReplyTo' => null,
                            'EnqueuedTimeUtc' => null,
                            'SequenceNumber' => null,
                            'TimeToLive' => null,
                            'To' => null,
                            'ScheduledEnqueueTimeUtc' => null,
                            'ReplyToSessionId' => null,
                            'PartitionKey' => null
                        ]),
                    ],
                ]),
            ],
        ];
    }

    /**
     * The "BrokerProperties" properties must be kept in the stamp when defined
     */
    public function testCreateFromResponse(): void
    {
        $contentType = 'test-content-type';
        $correlationId = 'test-correlation-id';
        $sessionID = 'test-session-id';
        $deliveryCount = 1;
        $lockedUntilUtc = '1970-01-01 00:00:00';
        $lockToken = 'test-lock-token';
        $messageId = 'test-message-id';
        $label = 'test-label';
        $replyTo = 'test-reply-to';
        $enqueuedTimeUtc = '1970-01-01 00:00:00';
        $sequenceNumber = 2;
        $timeToLive = 3;
        $to = 'test-to';
        $scheduledEnqueueTimeUtc = '1970-01-01 00:00:00';
        $replyToSessionId = 'test-reply-to-session-id';
        $partitionKey = 'test-partition-key';
    
        $httpClient = new MockHttpClient([
            new MockResponse('', [
                'response_headers' => [
                    'BrokerProperties' => json_encode([
                        'ContentType' => $contentType,
                        'CorrelationId' => $correlationId,
                        'SessionID' => $sessionID,
                        'DeliveryCount' => $deliveryCount,
                        'LockedUntilUtc' => $lockedUntilUtc,
                        'LockToken' => $lockToken,
                        'MessageId' => $messageId,
                        'Label' => $label,
                        'ReplyTo' => $replyTo,
                        'EnqueuedTimeUtc' => $enqueuedTimeUtc,
                        'SequenceNumber' => $sequenceNumber,
                        'TimeToLive' => $timeToLive,
                        'To' => $to,
                        'ScheduledEnqueueTimeUtc' => $scheduledEnqueueTimeUtc,
                        'ReplyToSessionId' => $replyToSessionId,
                        'PartitionKey' => $partitionKey
                    ]),
                ],
            ])
        ]);

        $response = $httpClient->request('TEST', '/');
        $stamp = AzureBrokerPropertiesStamp::createFromResponse($response);


        self::assertSame($contentType, $stamp->getContentType());
        self::assertSame($correlationId, $stamp->getCorrelationId());
        self::assertSame($sessionID, $stamp->getSessionID());
        self::assertSame($deliveryCount, $stamp->getDeliveryCount());

        self::assertInstanceOf(\DateTimeInterface::class, $stamp->getLockedUntilUtc());
        self::assertSame($lockedUntilUtc, $stamp->getLockedUntilUtc()->format('Y-m-d H:i:s'));

        self::assertSame($lockToken, $stamp->getLockToken());
        self::assertSame($messageId, $stamp->getMessageId());
        self::assertSame($label, $stamp->getLabel());
        self::assertSame($replyTo, $stamp->getReplyTo());

        self::assertInstanceOf(\DateTimeInterface::class, $stamp->getEnqueuedTimeUtc());
        self::assertSame($enqueuedTimeUtc, $stamp->getEnqueuedTimeUtc()->format('Y-m-d H:i:s'));

        self::assertSame($sequenceNumber, $stamp->getSequenceNumber());
        self::assertSame($timeToLive, $stamp->getTimeToLive());
        self::assertSame($to, $stamp->getTo());

        self::assertInstanceOf(\DateTimeInterface::class, $stamp->getScheduledEnqueueTimeUtc());
        self::assertSame($scheduledEnqueueTimeUtc, $stamp->getScheduledEnqueueTimeUtc()->format('Y-m-d H:i:s'));

        self::assertSame($replyToSessionId, $stamp->getReplyToSessionId());
        self::assertSame($partitionKey, $stamp->getPartitionKey());
    }

    /**
     * The encoding of the BrokerProperties must encode in JSON, remove null values and format datetimes
     */
    public function testEncodeTo(): void
    {
        $stamp = new AzureBrokerPropertiesStamp(
            'test-content-type',
                null,
                null,
                null,
                new \DateTime('1970-01-01 00:00:00')
        );

        $json = $stamp->encode();
        self::assertJson($json);

        $properties = json_decode($json, true);
        self::assertArrayHasKey('ContentType', $properties);
        self::assertSame('test-content-type', $properties['ContentType']);
        self::assertArrayNotHasKey('CorrelationId', $properties);
        self::assertArrayHasKey('LockedUntilUtc', $properties);
        self::assertSame('1970-01-01 00:00:00', $properties['LockedUntilUtc']);
    }
}
