<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\EcommerceApi\Response\Entity;

use PHPUnit\Framework\TestCase;
use Wsei\Ecommerce\EcommerceApi\Response\Entity\PaymentUrlResponse;

class PaymentUrlResponseTest extends TestCase
{
    public function testCanCreateResponseWithPaymentUrlAndToken(): void
    {
        // Arrange
        $paymentUrl = 'https://stripe.com/checkout/session_123';
        $token = 'payment_token_abc';

        // Act
        $response = new PaymentUrlResponse($paymentUrl, $token);

        // Assert
        static::assertInstanceOf(PaymentUrlResponse::class, $response);
    }

    public function testResponseContainsCorrectData(): void
    {
        // Arrange
        $paymentUrl = 'https://payment.gateway.com/pay';
        $token = 'secure_token_xyz';

        // Act
        $response = new PaymentUrlResponse($paymentUrl, $token);
        $data = json_decode($response->getContent(), true);

        // Assert
        static::assertEquals($paymentUrl, $data['paymentUrl']);
        static::assertEquals($token, $data['token']);
        static::assertEquals('PaymentResponse', $data['apiDescription']);
    }

    public function testResponseHasCorrectStatusCode(): void
    {
        // Act
        $response = new PaymentUrlResponse('https://url.com', 'token');

        // Assert
        static::assertEquals(200, $response->getStatusCode());
    }
}
