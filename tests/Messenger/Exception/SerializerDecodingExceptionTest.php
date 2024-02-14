<?php

declare(strict_types=1);

namespace Tests\AymDev\MessengerAzureBundle\Messenger\Exception;

use AymDev\MessengerAzureBundle\Messenger\Exception\SerializerDecodingException;
use AymDev\MessengerAzureBundle\Messenger\Transport\EmptyMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

final class SerializerDecodingExceptionTest extends TestCase
{
    public function testGetMessageProperties(): void
    {
        $envelope = new Envelope(new EmptyMessage());

        $exception = new SerializerDecodingException($envelope);

        self::assertSame($envelope, $exception->getEnvelope());
    }
}
