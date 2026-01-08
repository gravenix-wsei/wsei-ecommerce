<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Entity;

use PHPUnit\Framework\TestCase;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Entity\PaymentSession;
use Wsei\Ecommerce\Framework\Payment\Stripe\PaymentSessionStatus;

class PaymentSessionTest extends TestCase
{
    public function testPaymentSessionIsCreatedWithDefaultValues(): void
    {
        // Act
        $session = new PaymentSession();

        // Assert
        static::assertNull($session->getId());
        static::assertNotNull($session->getToken());
        static::assertNotNull($session->getCreatedAt());
        static::assertNotNull($session->getExpiresAt());
        static::assertEquals(PaymentSessionStatus::ACTIVE, $session->getStatus());
        static::assertTrue($session->isActive());
    }

    public function testTokenIsGeneratedWithCorrectLength(): void
    {
        // Act
        $session = new PaymentSession();

        // Assert
        static::assertEquals(PaymentSession::TOKEN_LENGTH, strlen($session->getToken()));
    }

    public function testExpiresAtIsSetTo30MinutesFromNow(): void
    {
        // Act
        $before = new \DateTime('+30 minutes');
        $session = new PaymentSession();
        $after = new \DateTime('+30 minutes');

        // Assert - expiresAt should be within the range (allowing for execution time)
        static::assertGreaterThanOrEqual($before, $session->getExpiresAt());
        static::assertLessThanOrEqual($after, $session->getExpiresAt());
    }

    public function testCanSetAndGetOrder(): void
    {
        // Arrange
        $session = new PaymentSession();
        $order = $this->createMock(Order::class);

        // Act
        $result = $session->setOrder($order);

        // Assert
        static::assertSame($session, $result);
        static::assertSame($order, $session->getOrder());
    }

    public function testCanSetAndGetReturnUrl(): void
    {
        // Arrange
        $session = new PaymentSession();
        $returnUrl = 'https://shop.com/payment/return';

        // Act
        $result = $session->setReturnUrl($returnUrl);

        // Assert
        static::assertSame($session, $result);
        static::assertEquals($returnUrl, $session->getReturnUrl());
    }

    public function testCanSetAndGetStripeSessionId(): void
    {
        // Arrange
        $session = new PaymentSession();
        $stripeSessionId = 'cs_test_123456';

        // Act
        $result = $session->setStripeSessionId($stripeSessionId);

        // Assert
        static::assertSame($session, $result);
        static::assertEquals($stripeSessionId, $session->getStripeSessionId());
    }

    public function testCanSetAndGetPaymentIntentId(): void
    {
        // Arrange
        $session = new PaymentSession();
        $paymentIntentId = 'pi_123456';

        // Act
        $result = $session->setPaymentIntentId($paymentIntentId);

        // Assert
        static::assertSame($session, $result);
        static::assertEquals($paymentIntentId, $session->getPaymentIntentId());
    }

    public function testCanSetAndGetStatus(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Act
        $result = $session->setStatus(PaymentSessionStatus::COMPLETED);

        // Assert
        static::assertSame($session, $result);
        static::assertEquals(PaymentSessionStatus::COMPLETED, $session->getStatus());
    }

    public function testIsActiveReturnsTrueForActiveStatus(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Assert
        static::assertTrue($session->isActive());
    }

    public function testIsActiveReturnsFalseForNonActiveStatus(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setStatus(PaymentSessionStatus::CANCELLED);

        // Assert
        static::assertFalse($session->isActive());
    }

    public function testIsCancelledReturnsTrueForCancelledStatus(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setStatus(PaymentSessionStatus::CANCELLED);

        // Assert
        static::assertTrue($session->isCancelled());
    }

    public function testIsCancelledReturnsFalseForNonCancelledStatus(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Assert
        static::assertFalse($session->isCancelled());
    }

    public function testIsCompletedReturnsTrueForCompletedStatus(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setStatus(PaymentSessionStatus::COMPLETED);

        // Assert
        static::assertTrue($session->isCompleted());
    }

    public function testIsCompletedReturnsFalseForNonCompletedStatus(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Assert
        static::assertFalse($session->isCompleted());
    }

    public function testIsExpiredReturnsTrueWhenExpiryDatePassed(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setExpiresAt(new \DateTime('-1 hour'));

        // Assert
        static::assertTrue($session->isExpired());
    }

    public function testIsExpiredReturnsTrueForExpiredStatus(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setStatus(PaymentSessionStatus::EXPIRED);

        // Assert
        static::assertTrue($session->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenNotExpired(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setExpiresAt(new \DateTime('+1 hour'));

        // Assert
        static::assertFalse($session->isExpired());
    }

    public function testCancelChangesStatusToCancelled(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Act
        $session->cancel();

        // Assert
        static::assertEquals(PaymentSessionStatus::CANCELLED, $session->getStatus());
        static::assertTrue($session->isCancelled());
    }

    public function testCompleteChangesStatusToCompleted(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Act
        $session->complete();

        // Assert
        static::assertEquals(PaymentSessionStatus::COMPLETED, $session->getStatus());
        static::assertTrue($session->isCompleted());
    }

    public function testExpireChangesStatusToExpired(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Act
        $session->expire();

        // Assert
        static::assertEquals(PaymentSessionStatus::EXPIRED, $session->getStatus());
    }

    public function testGenerateCreatesUniqueTokens(): void
    {
        // Act
        $token1 = PaymentSession::generate();
        $token2 = PaymentSession::generate();
        $token3 = PaymentSession::generate();

        // Assert
        static::assertEquals(PaymentSession::TOKEN_LENGTH, strlen($token1));
        static::assertEquals(PaymentSession::TOKEN_LENGTH, strlen($token2));
        static::assertEquals(PaymentSession::TOKEN_LENGTH, strlen($token3));

        static::assertNotEquals($token1, $token2);
        static::assertNotEquals($token2, $token3);
        static::assertNotEquals($token1, $token3);
    }

    public function testGeneratedTokenContainsOnlyAllowedCharacters(): void
    {
        // Act
        $token = PaymentSession::generate();

        // Assert
        static::assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $token);
    }
}
