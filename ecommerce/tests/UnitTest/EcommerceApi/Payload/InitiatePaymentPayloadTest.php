<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\EcommerceApi\Payload;

use PHPUnit\Framework\TestCase;
use Wsei\Ecommerce\EcommerceApi\Payload\InitiatePaymentPayload;

class InitiatePaymentPayloadTest extends TestCase
{
    public function testCanCreatePayloadWithReturnUrl(): void
    {
        // Arrange
        $returnUrl = 'https://shop.com/payment/return';

        // Act
        $payload = new InitiatePaymentPayload($returnUrl);

        // Assert
        $this->assertEquals($returnUrl, $payload->returnUrl);
    }

    public function testReturnUrlIsPubliclyAccessible(): void
    {
        // Arrange
        $returnUrl = 'https://example.com/success';
        $payload = new InitiatePaymentPayload($returnUrl);

        // Act & Assert
        $this->assertSame($returnUrl, $payload->returnUrl);
    }
}
