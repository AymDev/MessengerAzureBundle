<?php

declare(strict_types=1);

namespace Messenger\Transport;

use AymDev\MessengerAzureBundle\Messenger\Transport\SasTokenGenerator;
use PHPUnit\Framework\TestCase;

class SasTokenGeneratorTest extends TestCase
{
    public function testGenerator(): void
    {
        $generator = new SasTokenGenerator(
            'http://Endpoint',
            'myAccessKeyName',
            'myAccessKey',
            60
        );

        $token = $generator->generateSharedAccessSignatureToken();

        self::assertStringStartsWith('SharedAccessSignature ', $token);
        parse_str(substr($token, strlen('SharedAccessSignature ')), $tokenParameters);

        self::assertArrayHasKey('sig', $tokenParameters);
        self::assertArrayHasKey('se', $tokenParameters);
        self::assertArrayHasKey('skn', $tokenParameters);
        self::assertArrayHasKey('sr', $tokenParameters);

        self::assertNotEmpty($tokenParameters['sig']);
        self::assertEqualsWithDelta(time() + 60, $tokenParameters['se'], 5);
        self::assertSame('myAccessKeyName', $tokenParameters['skn']);
        self::assertSame('http://endpoint', $tokenParameters['sr']);
    }
}
