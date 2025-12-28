<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Framework\Payment\Result;

use PHPUnit\Framework\TestCase;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Framework\Payment\Result\PaymentVerificationResult;

class PaymentVerificationResultTest extends TestCase
{
    public function testCanCreateSuccessfulVerificationResult(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $returnUrl = 'https://shop.com/success';

        // Act
        $result = PaymentVerificationResult::success($order, $returnUrl);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertSame($order, $result->getOrder());
        $this->assertEquals($returnUrl, $result->getReturnUrl());
        $this->assertTrue($result->hasReturnUrl());
        $this->assertNull($result->getMessage());
    }

    public function testCanCreateFailedVerificationResultWithMessage(): void
    {
        // Arrange
        $message = 'Payment was declined';

        // Act
        $result = PaymentVerificationResult::failure($message);

        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals($message, $result->getMessage());
        $this->assertNull($result->getOrder());
        $this->assertEquals('', $result->getReturnUrl());
        $this->assertFalse($result->hasReturnUrl());
    }

    public function testCanCreateFailedVerificationResultWithOrderAndReturnUrl(): void
    {
        // Arrange
        $message = 'Invalid payment status';
        $order = $this->createMock(Order::class);
        $returnUrl = 'https://shop.com/error';

        // Act
        $result = PaymentVerificationResult::failure($message, $order, $returnUrl);

        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals($message, $result->getMessage());
        $this->assertSame($order, $result->getOrder());
        $this->assertEquals($returnUrl, $result->getReturnUrl());
        $this->assertTrue($result->hasReturnUrl());
    }

    public function testHasReturnUrlReturnsFalseWhenReturnUrlIsNull(): void
    {
        // Act
        $result = PaymentVerificationResult::failure('Error');

        // Assert
        $this->assertFalse($result->hasReturnUrl());
    }

    public function testHasReturnUrlReturnsTrueWhenReturnUrlExists(): void
    {
        // Act
        $result = PaymentVerificationResult::failure('Error', null, 'https://shop.com');

        // Assert
        $this->assertTrue($result->hasReturnUrl());
    }
}
