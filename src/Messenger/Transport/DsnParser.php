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

        if (!is_string($options['entity_path'])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid "entity_path" (queue or topic) for the "%s" transport. Expected string, got %s',
                    $transportName,
                    get_debug_type($options['entity_path']),
                ),
                1643989596
            );
        }

        if (!is_string($options['subscription']) && null !== $options['subscription']) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid "subscription" for the "%s" transport. Expected string, got %s',
                    $transportName,
                    get_debug_type($options['subscription']),
                ),
                1643989596
            );
        }

        if (!is_int($options['token_expiry']) && !ctype_digit($options['token_expiry'])) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid "token_expiry" for the "%s" transport. Expected integer, got %s',
                    $transportName,
                    get_debug_type($options['token_expiry']),
                ),
                1643989596
            );
        }

        if (false === in_array($options['receive_mode'], self::RECEIVE_MODES, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid "%s" receive_mode for the "%s" transport. It must be one of: %s.',
                    is_scalar($options['receive_mode']) ?
                        $options['receive_mode'] :
                        get_debug_type($options['receive_mode']),
                    $transportName,
                    implode(', ', self::RECEIVE_MODES)
                ),
                1643994036
            );
        }

        return [
            'shared_access_key_name' => $this->pickOption(
                'shared_access_key_name',
                'user',
                $parsedUrl,
                $options,
                $transportName,
            ),
            'shared_access_key' => $this->pickOption(
                'shared_access_key',
                'pass',
                $parsedUrl,
                $options,
                $transportName,
            ),
            'namespace' => $this->pickOption(
                'namespace',
                'host',
                $parsedUrl,
                $options,
                $transportName,
            ),
            'entity_path' => $options['entity_path'],
            'subscription' => $options['subscription'],
            'token_expiry' => (int) $options['token_expiry'],
            'receive_mode' => $options['receive_mode'],
        ];
    }

    /**
     * @param array<string, int|string> $dsnParts
     * @param mixed[] $options
     */
    private function pickOption(
        string $optionName,
        string $dsnName,
        array $dsnParts,
        array $options,
        string $transportName
    ): string {
        $dsnValue = $dsnParts[$dsnName] ?? '';
        if ($dsnValue !== '') {
            return urldecode((string) $dsnValue);
        }

        if (isset($options[$optionName])) {
            if (!is_string($options[$optionName])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid "%s" for the "%s" transport. Expected string, got %s',
                        $optionName,
                        $transportName,
                        get_debug_type($options[$optionName]),
                    ),
                    1702724695
                );
            }

            return $options[$optionName];
        }

        throw new InvalidArgumentException(
            sprintf('Missing %s for the "%s" transport.', $optionName, $transportName),
            1702724695
        );
    }
}
