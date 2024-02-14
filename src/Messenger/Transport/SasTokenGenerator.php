<?php

declare(strict_types=1);

namespace AymDev\MessengerAzureBundle\Messenger\Transport;

class SasTokenGenerator
{
    public function __construct(
        private readonly string $endpoint,
        private readonly string $accessKeyName,
        private readonly string $accessKey,
        private readonly int $tokenExpiry
    ) {
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
