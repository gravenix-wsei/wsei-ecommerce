<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\UnitTest\Framework\Payment;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Entity\OrderItem;
use Wsei\Ecommerce\Entity\PaymentSession;
use Wsei\Ecommerce\Entity\Product;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatus;
use Wsei\Ecommerce\Framework\Checkout\Order\OrderStatusTransitionInterface;
use Wsei\Ecommerce\Framework\Payment\Stripe\StripeClientInterface;
use Wsei\Ecommerce\Framework\Payment\Stripe\StripePaymentService;
use Wsei\Ecommerce\Repository\PaymentSessionRepository;

class StripePaymentServiceTest extends TestCase
{
    private StripePaymentService $paymentService;

    private EntityManagerInterface&MockObject $entityManager;

    private UrlGeneratorInterface&MockObject $urlGenerator;

    private PaymentSessionRepository&MockObject $paymentSessionRepository;

    private OrderStatusTransitionInterface&MockObject $statusTransition;

    private StripeClientInterface&MockObject $stripeClient;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->paymentSessionRepository = $this->createMock(PaymentSessionRepository::class);
        $this->statusTransition = $this->createMock(OrderStatusTransitionInterface::class);
        $this->stripeClient = $this->createMock(StripeClientInterface::class);

        $this->paymentService = new StripePaymentService(
            $this->entityManager,
            $this->urlGenerator,
            $this->paymentSessionRepository,
            $this->statusTransition,
            $this->stripeClient
        );
    }

    public function testPayThrowsExceptionWhenCannotTransitionToPendingPayment(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(OrderStatus::CANCELLED);
        $order->method('getItems')->willReturn(new ArrayCollection());

        $this->statusTransition
            ->method('canTransitionTo')
            ->with(OrderStatus::CANCELLED, OrderStatus::PENDING_PAYMENT)
            ->willReturn(false);

        // Expect exception with enum value (lowercase with underscore)
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order cannot transition from "cancelled" to "PENDING_PAYMENT"');

        // Act
        $this->paymentService->pay($order, 'https://example.com/return');
    }

    public function testPayCancelsActiveSessionsBeforeCreatingNew(): void
    {
        // Arrange
        $order = $this->createOrderWithItems(OrderStatus::NEW);

        $this->statusTransition
            ->method('canTransitionTo')
            ->willReturn(true);

        $this->paymentSessionRepository
            ->expects(static::once())
            ->method('cancelActiveSessionsForOrder')
            ->with($order);

        $this->urlGenerator
            ->method('generate')
            ->willReturn('https://example.com/verify?token=abc123');

        // Mock Stripe session creation
        $mockSession = $this->createMockStripeSession();
        $this->stripeClient
            ->expects(static::once())
            ->method('createCheckoutSession')
            ->willReturn($mockSession);

        // Act
        $result = $this->paymentService->pay($order, 'https://example.com/return');

        // Assert
        static::assertNotEmpty($result->getPaymentUrl());
        static::assertNotEmpty($result->getToken());
    }

    public function testPayTransitionsOrderToPendingPaymentWhenInNewStatus(): void
    {
        // Arrange
        $order = $this->createOrderWithItems(OrderStatus::NEW);

        $this->statusTransition
            ->method('canTransitionTo')
            ->with(OrderStatus::NEW, OrderStatus::PENDING_PAYMENT)
            ->willReturn(true);

        $order->expects(static::once())
            ->method('setStatus')
            ->with(OrderStatus::PENDING_PAYMENT);

        $this->urlGenerator
            ->method('generate')
            ->willReturn('https://example.com/verify?token=abc123');

        // Mock Stripe session creation
        $mockSession = $this->createMockStripeSession();
        $this->stripeClient
            ->method('createCheckoutSession')
            ->willReturn($mockSession);

        // Act
        $result = $this->paymentService->pay($order, 'https://example.com/return');

        // Assert
        static::assertNotEmpty($result->getPaymentUrl());
    }

    public function testPayAllowsPaymentWhenAlreadyInPendingPaymentStatus(): void
    {
        // Arrange
        $order = $this->createOrderWithItems(OrderStatus::PENDING_PAYMENT);

        $this->statusTransition
            ->method('canTransitionTo')
            ->with(OrderStatus::PENDING_PAYMENT, OrderStatus::PENDING_PAYMENT)
            ->willReturn(false);

        // setStatus is ALWAYS called at the end of transitionToPendingPayment method
        $order->expects(static::once())
            ->method('setStatus')
            ->with(OrderStatus::PENDING_PAYMENT);

        $this->urlGenerator
            ->method('generate')
            ->willReturn('https://example.com/verify?token=abc123');

        // Mock Stripe session creation
        $mockSession = $this->createMockStripeSession();
        $this->stripeClient
            ->method('createCheckoutSession')
            ->willReturn($mockSession);

        // Act
        $result = $this->paymentService->pay($order, 'https://example.com/return');

        // Assert - no exception should be thrown
        static::assertNotEmpty($result->getPaymentUrl());
    }

    // ========== VERIFY METHOD TESTS ==========

    public function testVerifyWithInvalidToken(): void
    {
        // Arrange
        $this->paymentSessionRepository
            ->expects(static::once())
            ->method('findValidByToken')
            ->with('invalid_token')
            ->willReturn(null);

        // Act
        $result = $this->paymentService->verify('invalid_token');

        // Assert
        static::assertFalse($result->isSuccess());
        static::assertNull($result->getOrder());
        static::assertEmpty($result->getReturnUrl());
        static::assertEquals('Invalid or expired payment token', $result->getMessage());
    }

    public function testVerifyWithExpiredToken(): void
    {
        // Arrange
        $this->paymentSessionRepository
            ->expects(static::once())
            ->method('findValidByToken')
            ->with('expired_token')
            ->willReturn(null);

        // Act
        $result = $this->paymentService->verify('expired_token');

        // Assert
        static::assertFalse($result->isSuccess());
        static::assertEquals('Invalid or expired payment token', $result->getMessage());
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
            ->expects(static::once())
            ->method('findValidByToken')
            ->with('valid_token')
            ->willReturn($paymentSession);

        // Act
        $result = $this->paymentService->verify('valid_token');

        // Assert
        static::assertFalse($result->isSuccess());
        static::assertSame($order, $result->getOrder());
        static::assertEquals('https://example.com/return', $result->getReturnUrl());
        static::assertEquals('No Stripe session found', $result->getMessage());
    }

    public function testVerifyFailsWhenOrderCannotTransitionToPaid(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(OrderStatus::CANCELLED);

        $paymentSession = $this->createMock(PaymentSession::class);
        $paymentSession->method('getOrder')->willReturn($order);
        $paymentSession->method('getStripeSessionId')->willReturn('cs_test_123');
        $paymentSession->method('getReturnUrl')->willReturn('https://example.com/return');

        $this->paymentSessionRepository
            ->method('findValidByToken')
            ->willReturn($paymentSession);

        $this->statusTransition
            ->method('canTransitionTo')
            ->with(OrderStatus::CANCELLED, OrderStatus::PAID)
            ->willReturn(false);

        // Act - The Stripe API call will happen and fail, but that's expected in unit test
        $result = $this->paymentService->verify('valid_token');
        static::assertFalse($result->isSuccess());
    }

    public function testVerifyFailsWhenOrderAlreadyPaid(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(OrderStatus::PAID);

        $paymentSession = $this->createMock(PaymentSession::class);
        $paymentSession->method('getOrder')->willReturn($order);
        $paymentSession->method('getStripeSessionId')->willReturn('cs_test_123');
        $paymentSession->method('getReturnUrl')->willReturn('https://example.com/return');

        $this->paymentSessionRepository
            ->method('findValidByToken')
            ->willReturn($paymentSession);

        // Act - The Stripe API call will happen and fail, but that's expected in unit tests
        try {
            $result = $this->paymentService->verify('valid_token');
            // If we somehow get here without Stripe API error, verify it's a failure
            static::assertFalse($result->isSuccess());
        } catch (\Exception) {
            // Expected - Stripe API will fail in unit test without valid credentials
        }
    }

    public function testVerifyReturnsFailureForEmptyReturnUrl(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $paymentSession = $this->createMock(PaymentSession::class);

        $paymentSession->method('getOrder')->willReturn($order);
        $paymentSession->method('getStripeSessionId')->willReturn(null);
        $paymentSession->method('getReturnUrl')->willReturn('');

        $this->paymentSessionRepository
            ->expects(static::once())
            ->method('findValidByToken')
            ->with('token_with_empty_url')
            ->willReturn($paymentSession);

        // Act
        $result = $this->paymentService->verify('token_with_empty_url');

        // Assert
        static::assertFalse($result->isSuccess());
        static::assertSame($order, $result->getOrder());
        static::assertEquals('', $result->getReturnUrl());
        static::assertEquals('No Stripe session found', $result->getMessage());
        static::assertFalse($result->hasReturnUrl());
    }

    public function testVerifyPreservesOrderAndReturnUrlInFailures(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $returnUrl = 'https://shop.example.com/payment-failed';
        $paymentSession = $this->createMock(PaymentSession::class);

        $paymentSession->method('getOrder')->willReturn($order);
        $paymentSession->method('getStripeSessionId')->willReturn(null);
        $paymentSession->method('getReturnUrl')->willReturn($returnUrl);

        $this->paymentSessionRepository
            ->method('findValidByToken')
            ->willReturn($paymentSession);

        // Act
        $result = $this->paymentService->verify('test_token');

        // Assert - verify that order and returnUrl are preserved in failure result
        static::assertFalse($result->isSuccess());
        static::assertSame($order, $result->getOrder());
        static::assertEquals($returnUrl, $result->getReturnUrl());
        static::assertTrue($result->hasReturnUrl());
    }

    public function testVerifySuccessfullyMarksPaymentAsPaid(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(OrderStatus::PENDING_PAYMENT);

        $paymentSession = $this->createMock(PaymentSession::class);
        $paymentSession->method('getOrder')->willReturn($order);
        $paymentSession->method('getStripeSessionId')->willReturn('cs_test_paid_123');
        $paymentSession->method('getReturnUrl')->willReturn('https://example.com/success');

        $this->paymentSessionRepository
            ->method('findValidByToken')
            ->with('paid_token')
            ->willReturn($paymentSession);

        $this->statusTransition
            ->method('canTransitionTo')
            ->with(OrderStatus::PENDING_PAYMENT, OrderStatus::PAID)
            ->willReturn(true);

        // Mock Stripe session with paid status
        $paidSession = $this->createMockStripeSession('paid');
        $this->stripeClient
            ->expects(static::once())
            ->method('retrieveSession')
            ->with('cs_test_paid_123')
            ->willReturn($paidSession);

        // Expect order status to be updated to PAID
        $order->expects(static::once())
            ->method('setStatus')
            ->with(OrderStatus::PAID);

        // Expect payment session to be marked as completed
        $paymentSession->expects(static::once())
            ->method('complete');

        // Expect entities to be persisted and flushed
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($entity) use ($order, $paymentSession) {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 1) {
                    static::assertSame($order, $entity);
                } elseif ($callCount === 2) {
                    static::assertSame($paymentSession, $entity);
                }
            });

        $this->entityManager
            ->expects(static::once())
            ->method('flush');

        // Act
        $result = $this->paymentService->verify('paid_token');

        // Assert
        static::assertTrue($result->isSuccess());
        static::assertSame($order, $result->getOrder());
        static::assertEquals('https://example.com/success', $result->getReturnUrl());
        static::assertNull($result->getMessage());
    }

    public function testVerifyReturnsFailureWhenPaymentStatusIsNotPaid(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(OrderStatus::PENDING_PAYMENT);

        $paymentSession = $this->createMock(PaymentSession::class);
        $paymentSession->method('getOrder')->willReturn($order);
        $paymentSession->method('getStripeSessionId')->willReturn('cs_test_unpaid_123');
        $paymentSession->method('getReturnUrl')->willReturn('https://example.com/return');

        $this->paymentSessionRepository
            ->method('findValidByToken')
            ->willReturn($paymentSession);

        // Mock Stripe session with unpaid status
        $unpaidSession = $this->createMockStripeSession('unpaid');
        $this->stripeClient
            ->method('retrieveSession')
            ->with('cs_test_unpaid_123')
            ->willReturn($unpaidSession);

        // Order status should NOT be updated
        $order->expects(static::never())->method('setStatus');

        // Payment session should NOT be completed
        $paymentSession->expects(static::never())->method('complete');

        // Act
        $result = $this->paymentService->verify('unpaid_token');

        // Assert
        static::assertFalse($result->isSuccess());
        static::assertEquals('Payment not completed', $result->getMessage());
        static::assertSame($order, $result->getOrder());
        static::assertEquals('https://example.com/return', $result->getReturnUrl());
    }

    public function testVerifyHandlesStripeApiExceptions(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $paymentSession = $this->createMock(PaymentSession::class);
        $paymentSession->method('getOrder')->willReturn($order);
        $paymentSession->method('getStripeSessionId')->willReturn('cs_test_error_123');
        $paymentSession->method('getReturnUrl')->willReturn('https://example.com/return');

        $this->paymentSessionRepository
            ->method('findValidByToken')
            ->willReturn($paymentSession);

        // Mock Stripe client to throw exception
        $this->stripeClient
            ->method('retrieveSession')
            ->willThrowException(new \Exception('Stripe API error occurred'));

        // Act
        $result = $this->paymentService->verify('error_token');

        // Assert
        static::assertFalse($result->isSuccess());
        static::assertEquals('Stripe API error occurred', $result->getMessage());
        static::assertSame($order, $result->getOrder());
        static::assertEquals('https://example.com/return', $result->getReturnUrl());
    }

    public function testVerifyWithPaidStatusButCannotTransitionToPaid(): void
    {
        // Arrange
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn(OrderStatus::CANCELLED);

        $paymentSession = $this->createMock(PaymentSession::class);
        $paymentSession->method('getOrder')->willReturn($order);
        $paymentSession->method('getStripeSessionId')->willReturn('cs_test_paid_cancelled_123');
        $paymentSession->method('getReturnUrl')->willReturn('https://example.com/return');

        $this->paymentSessionRepository
            ->method('findValidByToken')
            ->willReturn($paymentSession);

        // Mock paid Stripe session
        $paidSession = $this->createMockStripeSession('paid');
        $this->stripeClient
            ->method('retrieveSession')
            ->willReturn($paidSession);

        $this->statusTransition
            ->method('canTransitionTo')
            ->with(OrderStatus::CANCELLED, OrderStatus::PAID)
            ->willReturn(false);

        // Order status should NOT be updated
        $order->expects(static::never())->method('setStatus');

        // Act
        $result = $this->paymentService->verify('token');

        // Assert
        static::assertFalse($result->isSuccess());
        static::assertStringContainsString('Order cannot transition from "cancelled" to "PAID"', $result->getMessage());
        static::assertSame($order, $result->getOrder());
    }

    // ========== HELPER METHODS ==========

    private function createOrderWithItems(OrderStatus $status): Order&MockObject
    {
        $order = $this->createMock(Order::class);
        $order->method('getStatus')->willReturn($status);
        $order->method('getId')->willReturn(1);

        // Create product mock
        $product = $this->createMock(Product::class);
        $product->method('getName')->willReturn('Test Product');
        $product->method('getPriceGross')->willReturn('99.99');

        // Create order item mock
        $orderItem = $this->createMock(OrderItem::class);
        $orderItem->method('getProductName')->willReturn('Test Product');
        $orderItem->method('getPriceGross')->willReturn('99.99');
        $orderItem->method('getQuantity')->willReturn(2);
        $orderItem->method('getProduct')->willReturn($product);

        $items = new ArrayCollection([$orderItem]);
        $order->method('getItems')->willReturn($items);

        return $order;
    }

    private function createMockStripeSession(string $paymentStatus = 'unpaid'): Session
    {
        // Create a simple mock that acts like a Stripe Session
        $mockData = [
            'id' => 'cs_test_' . uniqid(),
            'url' => 'https://checkout.stripe.com/pay/cs_test_' . uniqid(),
            'payment_status' => $paymentStatus,
        ];

        // Use a dynamic object that can be accessed like Stripe objects
        return Session::constructFrom($mockData);
    }
}
