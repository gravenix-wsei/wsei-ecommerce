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
        static::assertTrue($result->isSuccess());
        static::assertSame($order, $result->getOrder());
        static::assertEquals($returnUrl, $result->getReturnUrl());
        static::assertTrue($result->hasReturnUrl());
        static::assertNull($result->getMessage());
    }

    public function testCanCreateFailedVerificationResultWithMessage(): void
    {
        // Arrange
        $message = 'Payment was declined';

        // Act
        $result = PaymentVerificationResult::failure($message);

        // Assert
        static::assertFalse($result->isSuccess());
        static::assertEquals($message, $result->getMessage());
        static::assertNull($result->getOrder());
        static::assertEquals('', $result->getReturnUrl());
        static::assertFalse($result->hasReturnUrl());
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
        static::assertFalse($result->isSuccess());
        static::assertEquals($message, $result->getMessage());
        static::assertSame($order, $result->getOrder());
        static::assertEquals($returnUrl, $result->getReturnUrl());
        static::assertTrue($result->hasReturnUrl());
    }

    public function testHasReturnUrlReturnsFalseWhenReturnUrlIsNull(): void
    {
        // Act
        $result = PaymentVerificationResult::failure('Error');

        // Assert
        static::assertFalse($result->hasReturnUrl());
    }

    public function testHasReturnUrlReturnsTrueWhenReturnUrlExists(): void
    {
        // Act
        $result = PaymentVerificationResult::failure('Error', null, 'https://shop.com');

        // Assert
        static::assertTrue($result->hasReturnUrl());
    }
}
