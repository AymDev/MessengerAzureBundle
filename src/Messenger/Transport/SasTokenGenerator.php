<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Transport;

class SasTokenGenerator
{
    /** @var string */
    private $endpoint;

    /** @var string */
    private $accessKeyName;

    /** @var string */
    private $accessKey;

    /** @var int */
    private $tokenExpiry;

    public function __construct(string $endpoint, string $accessKeyName, string $accessKey, int $tokenExpiry)
    {
        $this->endpoint = $endpoint;
        $this->accessKeyName = $accessKeyName;
        $this->accessKey = $accessKey;
        $this->tokenExpiry = $tokenExpiry;
    }

    /**
     * Generate the SAS token used to authenticate on Azure Service Bus REST API.
     */
    public function generateSharedAccessSignatureToken(): string
    {
        // Token expiry instant
        $expiry = time() + $this->tokenExpiry;

        // URL-encoded URI of the resource being accessed
        $resource = strtolower(rawurlencode(strtolower($this->endpoint)));

        // URL-encoded HMAC SHA256 signature
        $toSign = $resource . "\n" . $expiry;
        $signature = rawurlencode(base64_encode(hash_hmac('sha256', $toSign, $this->accessKey, true)));

        return sprintf(
            'SharedAccessSignature sig=%s&se=%d&skn=%s&sr=%s',
            $signature,
            $expiry,
            $this->accessKeyName,
            $resource
        );
    }
}
