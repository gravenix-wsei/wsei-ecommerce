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
        $this->assertNull($session->getId());
        $this->assertNotNull($session->getToken());
        $this->assertNotNull($session->getCreatedAt());
        $this->assertNotNull($session->getExpiresAt());
        $this->assertEquals(PaymentSessionStatus::ACTIVE, $session->getStatus());
        $this->assertTrue($session->isActive());
    }

    public function testTokenIsGeneratedWithCorrectLength(): void
    {
        // Act
        $session = new PaymentSession();

        // Assert
        $this->assertEquals(PaymentSession::TOKEN_LENGTH, strlen($session->getToken()));
    }

    public function testExpiresAtIsSetTo30MinutesFromNow(): void
    {
        // Act
        $before = new \DateTime('+30 minutes');
        $session = new PaymentSession();
        $after = new \DateTime('+30 minutes');

        // Assert - expiresAt should be within the range (allowing for execution time)
        $this->assertGreaterThanOrEqual($before, $session->getExpiresAt());
        $this->assertLessThanOrEqual($after, $session->getExpiresAt());
    }

    public function testCanSetAndGetOrder(): void
    {
        // Arrange
        $session = new PaymentSession();
        $order = $this->createMock(Order::class);

        // Act
        $result = $session->setOrder($order);

        // Assert
        $this->assertSame($session, $result);
        $this->assertSame($order, $session->getOrder());
    }

    public function testCanSetAndGetReturnUrl(): void
    {
        // Arrange
        $session = new PaymentSession();
        $returnUrl = 'https://shop.com/payment/return';

        // Act
        $result = $session->setReturnUrl($returnUrl);

        // Assert
        $this->assertSame($session, $result);
        $this->assertEquals($returnUrl, $session->getReturnUrl());
    }

    public function testCanSetAndGetStripeSessionId(): void
    {
        // Arrange
        $session = new PaymentSession();
        $stripeSessionId = 'cs_test_123456';

        // Act
        $result = $session->setStripeSessionId($stripeSessionId);

        // Assert
        $this->assertSame($session, $result);
        $this->assertEquals($stripeSessionId, $session->getStripeSessionId());
    }

    public function testCanSetAndGetPaymentIntentId(): void
    {
        // Arrange
        $session = new PaymentSession();
        $paymentIntentId = 'pi_123456';

        // Act
        $result = $session->setPaymentIntentId($paymentIntentId);

        // Assert
        $this->assertSame($session, $result);
        $this->assertEquals($paymentIntentId, $session->getPaymentIntentId());
    }

    public function testCanSetAndGetStatus(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Act
        $result = $session->setStatus(PaymentSessionStatus::COMPLETED);

        // Assert
        $this->assertSame($session, $result);
        $this->assertEquals(PaymentSessionStatus::COMPLETED, $session->getStatus());
    }

    public function testIsActiveReturnsTrueForActiveStatus(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Assert
        $this->assertTrue($session->isActive());
    }

    public function testIsActiveReturnsFalseForNonActiveStatus(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setStatus(PaymentSessionStatus::CANCELLED);

        // Assert
        $this->assertFalse($session->isActive());
    }

    public function testIsCancelledReturnsTrueForCancelledStatus(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setStatus(PaymentSessionStatus::CANCELLED);

        // Assert
        $this->assertTrue($session->isCancelled());
    }

    public function testIsCancelledReturnsFalseForNonCancelledStatus(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Assert
        $this->assertFalse($session->isCancelled());
    }

    public function testIsCompletedReturnsTrueForCompletedStatus(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setStatus(PaymentSessionStatus::COMPLETED);

        // Assert
        $this->assertTrue($session->isCompleted());
    }

    public function testIsCompletedReturnsFalseForNonCompletedStatus(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Assert
        $this->assertFalse($session->isCompleted());
    }

    public function testIsExpiredReturnsTrueWhenExpiryDatePassed(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setExpiresAt(new \DateTime('-1 hour'));

        // Assert
        $this->assertTrue($session->isExpired());
    }

    public function testIsExpiredReturnsTrueForExpiredStatus(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setStatus(PaymentSessionStatus::EXPIRED);

        // Assert
        $this->assertTrue($session->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenNotExpired(): void
    {
        // Arrange
        $session = new PaymentSession();
        $session->setExpiresAt(new \DateTime('+1 hour'));

        // Assert
        $this->assertFalse($session->isExpired());
    }

    public function testCancelChangesStatusToCancelled(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Act
        $session->cancel();

        // Assert
        $this->assertEquals(PaymentSessionStatus::CANCELLED, $session->getStatus());
        $this->assertTrue($session->isCancelled());
    }

    public function testCompleteChangesStatusToCompleted(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Act
        $session->complete();

        // Assert
        $this->assertEquals(PaymentSessionStatus::COMPLETED, $session->getStatus());
        $this->assertTrue($session->isCompleted());
    }

    public function testExpireChangesStatusToExpired(): void
    {
        // Arrange
        $session = new PaymentSession();

        // Act
        $session->expire();

        // Assert
        $this->assertEquals(PaymentSessionStatus::EXPIRED, $session->getStatus());
    }

    public function testGenerateCreatesUniqueTokens(): void
    {
        // Act
        $token1 = PaymentSession::generate();
        $token2 = PaymentSession::generate();
        $token3 = PaymentSession::generate();

        // Assert
        $this->assertEquals(PaymentSession::TOKEN_LENGTH, strlen($token1));
        $this->assertEquals(PaymentSession::TOKEN_LENGTH, strlen($token2));
        $this->assertEquals(PaymentSession::TOKEN_LENGTH, strlen($token3));

        $this->assertNotEquals($token1, $token2);
        $this->assertNotEquals($token2, $token3);
        $this->assertNotEquals($token1, $token3);
    }

    public function testGeneratedTokenContainsOnlyAllowedCharacters(): void
    {
        // Act
        $token = PaymentSession::generate();

        // Assert
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $token);
    }
}
