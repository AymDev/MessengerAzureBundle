<?php

declare(strict_types=1);

namespace Messenger\Transport;

use AymDev\MessengerAzureBundle\Messenger\Transport\DsnParser;
use PHPUnit\Framework\TestCase;

class DsnParserTest extends TestCase
{
    public function testParseValidDsn(): void
    {
        $parser = new DsnParser();
        $result = $parser->parseDsn('azure://key-name:key-value@namespace-name', 'my-transport');

        self::assertSame([
            'shared_access_key_name' => 'key-name',
            'shared_access_key' => 'key-value',
            'namespace' => 'namespace-name',
        ], $result);
    }

    public function testParseInvalidDsnThrowsException(): void
    {
        $parser = new DsnParser();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid Azure Service Bus DSN for the "my-transport" transport. ' .
            'It must be in the following format: azure://SharedAccessKeyName:SharedAccessKey@namespace'
        );
        $this->expectExceptionCode(1643988474);

        $parser->parseDsn('', 'my-transport');
    }
}
