<?php

declare(strict_types=1);

namespace Tests\AymDev\MessengerAzureBundle\Messenger\Transport;

use AymDev\MessengerAzureBundle\Messenger\Transport\AzureTransport;
use AymDev\MessengerAzureBundle\Messenger\Stamp\AzureBrokerPropertiesStamp;
use AymDev\MessengerAzureBundle\Messenger\Stamp\AzureReceivedStamp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class AzureTransportTest extends TestCase
{
    /**
     * Exceptions related to the HTTP Call must be converted to a transport exception
     */
    public function testGetWithHttpCallErrorThrowsTransportException(): void
    {
        self::expectException(TransportException::class);
        self::expectExceptionCode(1644315123);

        $httpReceiver = new MockHttpClient(new MockResponse('test-body', [
            'http_code' => 418
        ]));

        $transport = new AzureTransport(
            self::createMock(SerializerInterface::class),
            new MockHttpClient(),
            $httpReceiver,
            AzureTransport::RECEIVE_MODE_PEEK_LOCK
        );

        $transport->get();
    }

    /**
     * The transport must return an empty array when Azure Service Bus returns a 204
     */
    public function testGetWithNoMessage(): void
    {
        $httpReceiver = new MockHttpClient(new MockResponse('test-body', [
            'http_code' => 204
        ]));

        $transport = new AzureTransport(
            self::createMock(SerializerInterface::class),
            new MockHttpClient(),
            $httpReceiver,
            AzureTransport::RECEIVE_MODE_PEEK_LOCK
        );

        self::assertSame([], $transport->get());
    }

    /**
     * An exception must be thrown if Azure Service Bus returns an unexpected status code
     */
    public function testGetWithUnexpectedStatusCode(): void
    {
        self::expectException(TransportException::class);
        self::expectExceptionCode(1644315645);

        $httpReceiver = new MockHttpClient(new MockResponse('test-body', [
            'http_code' => 299
        ]));

        $transport = new AzureTransport(
            self::createMock(SerializerInterface::class),
            new MockHttpClient(),
            $httpReceiver,
            AzureTransport::RECEIVE_MODE_PEEK_LOCK
        );

        $transport->get();
    }

    /**
     * Read messages must be returned in an envelope with specific stamps
     * @dataProvider provideValidGetCases
     */
    public function testGetHasStamps(int $statusCode, string $receiveMode, bool $hasBrokerPropertyMessageId): void
    {
        $serializer = self::createMock(SerializerInterface::class);
        $serializer->expects(self::once())
            ->method('decode')
            ->willReturn(new Envelope(new class {}));

        $httpReceiver = new MockHttpClient(new MockResponse('test-body', [
            'http_code' => $statusCode,
            'response_headers' => [
                'BrokerProperties' => json_encode(
                    $hasBrokerPropertyMessageId
                        ? ['MessageId' => 'test-message-id']
                        : []
                ),
            ],
        ]));

        $transport = new AzureTransport(
            $serializer,
            new MockHttpClient(),
            $httpReceiver,
            $receiveMode
        );

        $result = $transport->get();
        self::assertIsArray($result);
        self::assertCount(1, $result);

        $envelope = $result[0];
        self::assertInstanceOf(Envelope::class, $envelope);

        $azureReceivedStamp = $envelope->last(AzureReceivedStamp::class);
        self::assertInstanceOf(AzureReceivedStamp::class, $azureReceivedStamp);

        $azureBrokerPropertiesStamp = $envelope->last(AzureBrokerPropertiesStamp::class);
        self::assertInstanceOf(AzureBrokerPropertiesStamp::class, $azureBrokerPropertiesStamp);

        $messageIdStamp = $envelope->last(TransportMessageIdStamp::class);
        if ($hasBrokerPropertyMessageId) {
            self::assertInstanceOf(TransportMessageIdStamp::class, $messageIdStamp);
        } else {
            self::assertNull($messageIdStamp);
        }
    }

    /**
     * @return array{int, string, bool}
     */
    public function provideValidGetCases(): array
    {
        return [
            [
                200,
                AzureTransport::RECEIVE_MODE_RECEIVE_AND_DELETE,
                true,
            ],
            [
                200,
                AzureTransport::RECEIVE_MODE_RECEIVE_AND_DELETE,
                false,
            ],
            [
                201,
                AzureTransport::RECEIVE_MODE_PEEK_LOCK,
                true,
            ],
            [
                201,
                AzureTransport::RECEIVE_MODE_PEEK_LOCK,
                false,
            ],
        ];
    }

    /**
     * The message acknowledgment or rejection must not do anything on the Receive And Delete receive mode
     * @dataProvider provideDeletingMessageMethodNames
     */
    public function testAckRejectDoesNotDeleteOnReceiveAndDeleteMode(string $methodName): void
    {
        $receiver = self::createMock(HttpClientInterface::class);
        $receiver->expects(self::never())->method('request');

        $transport = new AzureTransport(
            self::createMock(SerializerInterface::class),
            new MockHttpClient(),
            $receiver,
            AzureTransport::RECEIVE_MODE_RECEIVE_AND_DELETE
        );

        $envelope = new Envelope(new class {});
        call_user_func([$transport, $methodName], $envelope);
    }

    /**
     * The message acknowledgment or rejection must delete using the delete location URL when available
     * @dataProvider provideDeletingMessageMethodNames
     */
    public function testAckRejectDeletesWithDeleteLocationWhenAvailable(string $methodName): void
    {
        $expectedUrl = 'https://test-domain-b.com/test-uri-b';

        $receiver = new MockHttpClient(
            function (string $method, string $url) use ($expectedUrl): ResponseInterface {
                self::assertSame('DELETE', $method);
                self::assertSame($expectedUrl, $url);

                return new MockResponse();
            },
            'https://test-domain-a.com/test-uri-a'
        );

        $transport = new AzureTransport(
            self::createMock(SerializerInterface::class),
            new MockHttpClient(),
            $receiver,
            AzureTransport::RECEIVE_MODE_PEEK_LOCK
        );

        $envelope = new Envelope(new class {}, [
            new AzureReceivedStamp('message', $expectedUrl),
        ]);
        call_user_func([$transport, $methodName], $envelope);
    }

    /**
     * An exception must be thrown if there are no delete location nor broker properties
     * @dataProvider provideDeletingMessageMethodNames
     */
    public function testAckRejectWithoutDeleteLocationOrBrokerProperties(string $methodName): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionCode(1644340687);

        $transport = new AzureTransport(
            self::createMock(SerializerInterface::class),
            new MockHttpClient(),
            new MockHttpClient(),
            AzureTransport::RECEIVE_MODE_PEEK_LOCK
        );


        $envelope = new Envelope(new class {}, [
            new AzureReceivedStamp('message', null),
        ]);
        call_user_func([$transport, $methodName], $envelope);
    }

    /**
     * An exception must be thrown if there are no MessageId nor SequenceNumber in the broker properties
     * @dataProvider provideDeletingMessageMethodNames
     */
    public function testAckRejectWithoutBrokerPropertiesMessageIdentifier(string $methodName): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionCode(1644340921);

        $transport = new AzureTransport(
            self::createMock(SerializerInterface::class),
            new MockHttpClient(),
            new MockHttpClient(),
            AzureTransport::RECEIVE_MODE_PEEK_LOCK
        );


        $envelope = new Envelope(new class {}, [
            new AzureBrokerPropertiesStamp(),
        ]);
        call_user_func([$transport, $methodName], $envelope);
    }

    /**
     * An exception must be thrown if there is a MessageId or SequenceNumber but no LockToken in the broker properties
     * @dataProvider provideBrokerPropertiesWithMissingLockToken
     */
    public function testAckRejectWithoutBrokerPropertiesLockToken(
        string $methodName,
        AzureBrokerPropertiesStamp $stamp
    ): void {
        self::expectException(\LogicException::class);
        self::expectExceptionCode(1644340926);

        $transport = new AzureTransport(
            self::createMock(SerializerInterface::class),
            new MockHttpClient(),
            new MockHttpClient(),
            AzureTransport::RECEIVE_MODE_PEEK_LOCK
        );


        $envelope = new Envelope(new class {}, [$stamp]);
        call_user_func([$transport, $methodName], $envelope);
    }

    /**
     * @return array{string, AzureBrokerPropertiesStamp}[]
     */
    public function provideBrokerPropertiesWithMissingLockToken(): iterable
    {
        $stamps = [
            new AzureBrokerPropertiesStamp(
                null,
                null,
                null,
                null,
                null,
                null,
                'test-message-id'
            ),
            new AzureBrokerPropertiesStamp(
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                42
            )
        ];

        foreach ($this->provideDeletingMessageMethodNames() as [$methodName]) {
            foreach ($stamps as $stamp) {
                yield [
                    $methodName,
                    $stamp
                ];
            }
        }
    }

    /**
     * The message acknowledgment or rejection must delete using the BrokerProperties when there is no delete location
     * but there is a message identifier and LockToken
     * @dataProvider provideDeletingMessageMethodNames
     */
    public function testAckRejectDeletesWithBrokerProperties(string $methodName): void
    {
        $messageId = 'test-message-id';
        $lockToken = 'test-lock-token';

        $baseUri = 'https://test-domain.com/test-uri/';
        $expectedUrl = sprintf('%smessages/%s/%s', $baseUri, $messageId, $lockToken);

        $receiver = new MockHttpClient(
            function (string $method, string $url) use ($expectedUrl): ResponseInterface {
                self::assertSame('DELETE', $method);
                self::assertSame($expectedUrl, $url);

                return new MockResponse();
            },
            $baseUri
        );

        $transport = new AzureTransport(
            self::createMock(SerializerInterface::class),
            new MockHttpClient(),
            $receiver,
            AzureTransport::RECEIVE_MODE_PEEK_LOCK
        );

        $envelope = new Envelope(new class {}, [
            new AzureBrokerPropertiesStamp(
                null,
                null,
                null,
                null,
                null,
                $lockToken,
                $messageId
            ),
        ]);
        call_user_func([$transport, $methodName], $envelope);
    }

    /**
     * An exception from the HTTP client during the message acknowledgment or rejection must be converted to a transport
     * exception
     * @dataProvider provideDeletingMessageMethodNames
     */
    public function testAckRejectThrowsOnHttpError(string $methodName): void
    {
        self::expectException(TransportException::class);
        self::expectExceptionCode(1644340210);

        $receiver = new MockHttpClient(new MockResponse('test-body', [
            'http_code' => 418
        ]));

        $transport = new AzureTransport(
            self::createMock(SerializerInterface::class),
            new MockHttpClient(),
            $receiver,
            AzureTransport::RECEIVE_MODE_PEEK_LOCK
        );

        $envelope = new Envelope(new class {}, [
            new AzureReceivedStamp('message', 'https://delete-location'),
        ]);
        call_user_func([$transport, $methodName], $envelope);
    }

    /**
     * @return string[][]
     */
    public function provideDeletingMessageMethodNames(): array
    {
        return [
            ['ack'],
            ['reject'],
        ];
    }

    /**
     * The BrokerProperties stamp must be used to generate a BrokerProperties HTTP header
     */
    public function testSendWithBrokerPropertiesStampGetsConvertedToHttpHeader(): void
    {
        $envelope = new Envelope(new class {}, [
            new AzureBrokerPropertiesStamp(),
        ]);

        $serializer = self::createMock(SerializerInterface::class);
        $serializer->expects(self::once())
            ->method('encode')
            ->with($envelope)
            ->willReturn(['body' => 'test-body']);

        $sender = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
            self::assertArrayHasKey('normalized_headers', $options);
            self::assertArrayHasKey('brokerproperties', $options['normalized_headers']);
            self::assertSame('BrokerProperties: {}', $options['normalized_headers']['brokerproperties'][0]);

            return new MockResponse();
        });

        $transport = new AzureTransport(
            $serializer,
            $sender,
            new MockHttpClient(),
            AzureTransport::RECEIVE_MODE_RECEIVE_AND_DELETE
        );

        $transport->send($envelope);
    }

    /**
     * The encoded enveloppe must have a "body" key
     */
    public function testSentEncodedEnvelopeMustHaveABody(): void
    {
        self::expectException(\LogicException::class);
        self::expectExceptionCode(1644403794);

        $envelope = new Envelope(new class {});

        $serializer = self::createMock(SerializerInterface::class);
        $serializer->expects(self::once())
            ->method('encode')
            ->with($envelope)
            ->willReturn([]);

        $transport = new AzureTransport(
            $serializer,
            new MockHttpClient(),
            new MockHttpClient(),
            AzureTransport::RECEIVE_MODE_RECEIVE_AND_DELETE
        );

        $transport->send($envelope);
    }

    /**
     * Headers from the encoded envelope must be passed as HTTP headers in the request
     */
    public function testSendEnvelopeHeadersArePassedAsHttpHeaders(): void
    {
        $envelope = new Envelope(new class {});

        $serializer = self::createMock(SerializerInterface::class);
        $serializer->expects(self::once())
            ->method('encode')
            ->with($envelope)
            ->willReturn([
                'body' => 'test-body',
                'headers' => [
                    'Test-Header' => 'test-header-value'
                ]
            ]);

        $sender = new MockHttpClient(function (string $method, string $url, array $options): ResponseInterface {
            self::assertArrayHasKey('normalized_headers', $options);
            self::assertArrayHasKey('test-header', $options['normalized_headers']);
            self::assertSame('Test-Header: test-header-value', $options['normalized_headers']['test-header'][0]);

            return new MockResponse();
        });

        $transport = new AzureTransport(
            $serializer,
            $sender,
            new MockHttpClient(),
            AzureTransport::RECEIVE_MODE_RECEIVE_AND_DELETE
        );

        $transport->send($envelope);
    }

    /**
     * Sent messages must send the envelope body
     */
    public function testSendMessageWithBody(): void
    {
        $envelope = new Envelope(new class {});

        $serializer = self::createMock(SerializerInterface::class);
        $serializer->expects(self::once())
            ->method('encode')
            ->with($envelope)
            ->willReturn(['body' => 'test-body']);

        $baseUri = 'https://test-domain.com/test-uri/';
        $expectedUrl = $baseUri . 'messages';

        $sender = new MockHttpClient(
            function (string $method, string $url, array $options) use ($expectedUrl): ResponseInterface {
                self::assertSame('POST', $method);
                self::assertSame($expectedUrl, $url);

                self::assertArrayHasKey('body', $options);
                self::assertSame('test-body', $options['body']);

                return new MockResponse();
            },
            $baseUri
        );

        $transport = new AzureTransport(
            $serializer,
            $sender,
            new MockHttpClient(),
            AzureTransport::RECEIVE_MODE_RECEIVE_AND_DELETE
        );

        $transport->send($envelope);
    }

    /**
     * Http exceptions thrown during sending must be converted to transport exceptions
     */
    public function testSendHttpExceptionThrowsTransportException(): void
    {
        self::expectException(TransportException::class);
        self::expectExceptionCode(1644415901);

        $envelope = new Envelope(new class {});

        $serializer = self::createMock(SerializerInterface::class);
        $serializer->expects(self::once())
            ->method('encode')
            ->with($envelope)
            ->willReturn(['body' => 'test-body']);

        $sender = new MockHttpClient(new MockResponse('', [
            'http_code' => 418,
        ]));

        $transport = new AzureTransport(
            $serializer,
            $sender,
            new MockHttpClient(),
            AzureTransport::RECEIVE_MODE_RECEIVE_AND_DELETE
        );

        $transport->send($envelope);
    }
}
