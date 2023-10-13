<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Transport;

use Symfony\Component\Messenger\Exception\InvalidArgumentException;

class DsnParser
{
    private const RECEIVE_MODES = [
        AzureTransport::RECEIVE_MODE_PEEK_LOCK,
        AzureTransport::RECEIVE_MODE_RECEIVE_AND_DELETE,
    ];

    private const DEFAULT_OPTIONS = [
        'shared_access_key_name' => null,
        'shared_access_key' => null,
        'namespace' => null,
        'entity_path' => null,
        'subscription' => null,
        'token_expiry' => 3600,
        'receive_mode' => AzureTransport::RECEIVE_MODE_PEEK_LOCK,
    ];

    /**
     * Parse the DSN and merges it with options.
     *
     * @param mixed[] $options
     * @return array{
     *     shared_access_key_name: string,
     *     shared_access_key: string,
     *     namespace: string,
     *     entity_path: string,
     *     subscription: string|null,
     *     token_expiry: int,
     *     receive_mode: AzureTransport::RECEIVE_MODE_*,
     * }
     */
    public function parseDsn(string $dsn, array $options, string $transportName): array
    {
        $parsedUrl = parse_url($dsn);
        if (false === $parsedUrl || ($parsedUrl['scheme'] ?? '') !== 'azure') {
            $message = sprintf('Invalid Azure Service Bus DSN for the "%s" transport. ', $transportName);
            $message .= 'It must be in the following format: azure://SharedAccessKeyName:SharedAccessKey@namespace';
            throw new InvalidArgumentException($message, 1643988474);
        }

        $query = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }

        // check for extra keys in options
        $optionsExtraKeys = array_diff(array_keys($options), array_keys(self::DEFAULT_OPTIONS));
        if (0 < count($optionsExtraKeys)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown option found: [%s]. Allowed options are [%s].',
                implode(', ', $optionsExtraKeys),
                implode(', ', array_keys(self::DEFAULT_OPTIONS))
            ));
        }

        // check for extra keys in query
        $queryExtraKeys = array_diff(array_keys($query), array_keys(self::DEFAULT_OPTIONS));
        if (0 < count($queryExtraKeys)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown option found in DSN: [%s]. Allowed options are [%s].',
                implode(', ', $queryExtraKeys),
                implode(', ', array_keys(self::DEFAULT_OPTIONS))
            ));
        }

        $options = $query + $options + self::DEFAULT_OPTIONS;
        /** @var array{
         *      shared_access_key_name: string|null,
         *      shared_access_key: string|null,
         *      namespace: string|null,
         *      entity_path: string|null,
         *      subscription: string|null,
         *      token_expiry: int|string,
         *      receive_mode: AzureTransport::RECEIVE_MODE_*,
         * } $options
         */

        $options['shared_access_key_name'] = $this->pickOption('shared_access_key_name', 'user', $parsedUrl, $options);
        $options['shared_access_key'] = $this->pickOption('shared_access_key', 'pass', $parsedUrl, $options);
        $options['namespace'] = $this->pickOption('namespace', 'host', $parsedUrl, $options);
        $options['token_expiry'] = (int) $options['token_expiry'];

        // Missing topic or queue name
        if (null === $options['entity_path']) {
            throw new InvalidArgumentException(
                sprintf('Missing entity_path (queue or topic) for the "%s" transport.', $transportName),
                1643989596
            );
        }

        // Invalid receive mode
        if (false === in_array($options['receive_mode'], self::RECEIVE_MODES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid "%s" receive_mode for the "%s" transport. It must be one of: %s.',
                    $options['receive_mode'],
                    $transportName,
                    implode(', ', self::RECEIVE_MODES)
                ),
                1643994036
            );
        }

        // @phpstan-ignore-next-line after all those array merges PHPstan gets confused about the result shape
        return $options;
    }

    /**
     * @param array<string, int|string> $dsnParts
     * @param array<string, mixed> $options
     * @return mixed
     */
    private function pickOption(string $optionName, string $dsnName, array $dsnParts, array $options)
    {
        $dsnValue = $dsnParts[$dsnName] ?? '';
        if ($dsnValue !== '') {
            return urldecode((string) $dsnValue);
        }

        if (isset($options[$optionName])) {
            return $options[$optionName];
        }

        return self::DEFAULT_OPTIONS[$optionName];
    }
}
