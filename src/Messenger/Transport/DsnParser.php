<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Transport;

class DsnParser
{
    /**
     * Parse the DSN to extract the namespace and shared access key
     * @return array{
     *     shared_access_key_name: string,
     *     shared_access_key: string,
     *     namespace: string,
     * }
     */
    public function parseDsn(string $dsn, string $transportName): array
    {
        if (1 !== preg_match('~^azure://(.+):(.+)@(.+)$~', $dsn, $matches)) {
            $message = sprintf('Invalid Azure Service Bus DSN for the "%s" transport. ', $transportName);
            $message .= 'It must be in the following format: azure://SharedAccessKeyName:SharedAccessKey@namespace';
            throw new \InvalidArgumentException($message, 1643988474);
        }

        return [
            'shared_access_key_name' => $matches[1],
            'shared_access_key' => $matches[2],
            'namespace' => $matches[3],
        ];
    }
}
