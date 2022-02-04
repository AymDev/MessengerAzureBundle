<?php

declare(strict_types=1);

namespace Tests\AymDev\MessengerAzureBundle;

use AymDev\MessengerAzureBundle\AymDevMessengerAzureBundle;
use AymDev\MessengerAzureBundle\DependencyInjection\AymDevMessengerAzureExtension;
use PHPUnit\Framework\TestCase;

final class AymDevMessengerAzureBundleTest extends TestCase
{
    /**
     * Ensure the extension will be loaded
     */
    public function testExtensionNameConsistency(): void
    {
        $bundle = new AymDevMessengerAzureBundle();
        $extension = $bundle->getContainerExtension();

        self::assertInstanceOf(AymDevMessengerAzureExtension::class, $extension);
    }
}
