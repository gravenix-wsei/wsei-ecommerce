<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Framework\Payment;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Entity\PaymentSession;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatusTransitionInterface;
use Wsei\Ecommerce\Framework\Payment\Result\PaymentVerificationResult;
use Wsei\Ecommerce\Framework\Payment\Stripe\StripePaymentService;
use Wsei\Ecommerce\Repository\PaymentSessionRepository;

class StripePaymentServiceTest extends TestCase
{
    private StripePaymentService $paymentService;

    private MockObject $entityManager;

    private MockObject $urlGenerator;

    private MockObject $paymentSessionRepository;

    private MockObject $statusTransition;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->paymentSessionRepository = $this->createMock(PaymentSessionRepository::class);
        $this->statusTransition = $this->createMock(OrderStatusTransitionInterface::class);

        $this->paymentService = new StripePaymentService(
            $this->entityManager,
            $this->urlGenerator,
            $this->paymentSessionRepository,
            $this->statusTransition,
            'sk_test_mock_key'
        );
    }

    public function testVerifyWithInvalidToken(): void
    {
        // Arrange
        $this->paymentSessionRepository
            ->expects($this->once())
            ->method('findValidByToken')
            ->with('invalid_token')
            ->willReturn(null);

        // Act
        $result = $this->paymentService->verify('invalid_token');

        // Assert;
        $this->assertFalse($result->isSuccess());
        $this->assertNull($result->getOrder());
        $this->assertEmpty($result->getReturnUrl());
        $this->assertEquals('Invalid or expired payment token', $result->getMessage());
    }

    public function testVerifyWithExpiredToken(): void
    {
        // Arrange - expired token returns null from repository
        $this->paymentSessionRepository
            ->expects($this->once())
            ->method('findValidByToken')
            ->with('expired_token')
            ->willReturn(null);

        // Act
        $result = $this->paymentService->verify('expired_token');

        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Invalid or expired payment token', $result->getMessage());
    }

    public function testVerifyWithMissingStripeSessionId(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $paymentSession = $this->createMock(PaymentSession::class);

        $paymentSession->method('getOrder')->willReturn($order);
        $paymentSession->method('getStripeSessionId')->willReturn(null);
        $paymentSession->method('getReturnUrl')->willReturn('https://example.com/return');

        $this->paymentSessionRepository
            ->expects($this->once())
            ->method('findValidByToken')
            ->with('valid_token')
            ->willReturn($paymentSession);

        // Act
        $result = $this->paymentService->verify('valid_token');

        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertSame($order, $result->getOrder());
        $this->assertEquals('https://example.com/return', $result->getReturnUrl());
        $this->assertEquals('No Stripe session found', $result->getMessage());
    }

    public function testSuccessFactoryMethod(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $returnUrl = 'https://example.com/success';

        // Act
        $result = PaymentVerificationResult::success($order, $returnUrl);

        // Assert
        $this->assertTrue($result->isSuccess());
        $this->assertSame($order, $result->getOrder());
        $this->assertEquals($returnUrl, $result->getReturnUrl());
        $this->assertNull($result->getMessage());
    }

    public function testFailureFactoryMethod(): void
    {
        // Arrange
        $message = 'Payment failed';
        $order = $this->createMock(Order::class);
        $returnUrl = 'https://example.com/error';

        // Act
        $result = PaymentVerificationResult::failure($message, $order, $returnUrl);

        // Assert
        $this->assertFalse($result->isSuccess());
        $this->assertEquals($message, $result->getMessage());
        $this->assertSame($order, $result->getOrder());
        $this->assertEquals($returnUrl, $result->getReturnUrl());
    }

    public function testHasReturnUrl(): void
    {
        // Test with return URL
        $resultWithUrl = PaymentVerificationResult::failure('Error', null, 'https://example.com');
        $this->assertTrue($resultWithUrl->hasReturnUrl());

        // Test without return URL
        $resultWithoutUrl = PaymentVerificationResult::failure('Error');
        $this->assertFalse($resultWithoutUrl->hasReturnUrl());
    }
}
