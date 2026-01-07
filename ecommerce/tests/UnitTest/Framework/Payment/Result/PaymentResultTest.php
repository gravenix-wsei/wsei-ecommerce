<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Framework\Payment\Result;

use PHPUnit\Framework\TestCase;
use Wsei\Ecommerce\Framework\Payment\Result\PaymentResult;

class PaymentResultTest extends TestCase
{
    public function testCanCreatePaymentResultWithValidData(): void
    {
        // Arrange & Act
        $result = new PaymentResult('https://payment.url', 'test_token_123');

        // Assert
        static::assertEquals('https://payment.url', $result->getPaymentUrl());
        static::assertEquals('test_token_123', $result->getToken());
    }

    public function testPaymentUrlCanBeRetrieved(): void
    {
        // Arrange
        $paymentUrl = 'https://stripe.com/checkout/session_abc123';
        $result = new PaymentResult($paymentUrl, 'token');

        // Act & Assert
        static::assertSame($paymentUrl, $result->getPaymentUrl());
    }

    public function testTokenCanBeRetrieved(): void
    {
        // Arrange
        $token = 'secure_token_xyz789';
        $result = new PaymentResult('https://payment.url', $token);

        // Act & Assert
        static::assertSame($token, $result->getToken());
    }
}
