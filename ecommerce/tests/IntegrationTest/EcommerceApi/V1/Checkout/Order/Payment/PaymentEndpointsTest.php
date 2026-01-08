<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Order\Payment;

use Wsei\Ecommerce\Repository\OrderRepository;
use Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Order\AbstractOrderPlacementTest;
use Wsei\Ecommerce\Tests\IntegrationTest\Mocked\Framework\Payment\MockPaymentService;

class PaymentEndpointsTest extends AbstractOrderPlacementTest
{
    private MockPaymentService $mockPaymentService;

    private OrderRepository $orderRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Get repositories and services
        $this->orderRepository = static::getContainer()->get(OrderRepository::class);

        // Get the existing MockPaymentService from container (it's configured as singleton)
        $this->mockPaymentService = static::getContainer()->get(
            'Wsei\Ecommerce\Tests\IntegrationTest\Mocked\Framework\Payment\MockPaymentService'
        );

        // Verify it's actually the MockPaymentService
        static::assertInstanceOf(MockPaymentService::class, $this->mockPaymentService);

        // Reset to clean state
        $this->mockPaymentService->reset();
    }

    protected function tearDown(): void
    {
        // Reset mock after each test
        if (isset($this->mockPaymentService)) {
            $this->mockPaymentService->reset();
        }
        parent::tearDown();
    }

    public function testPayEndpointSuccess(): void
    {
        // Arrange - create order
        $customer = $this->createCustomerWithToken('payment-test@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Test Product', 10, '25.00', '30.75');

        $this->addItemToCart($customer, $product->getId(), 2);
        $this->placeOrder($customer, $address->getId());

        $orderResponse = json_decode($this->client->getResponse()->getContent(), true);
        $orderId = $orderResponse['id'];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/order/pay/' . $orderId, [
            'returnUrl' => 'https://example.com/success',
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        static::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        static::assertArrayHasKey('paymentUrl', $response);
        static::assertArrayHasKey('token', $response);
        static::assertStringStartsWith('https://mock.stripe.com/checkout/', $response['paymentUrl']);
        static::assertStringStartsWith('mock_token_', $response['token']);
    }

    public function testPayEndpointFailsWithMockError(): void
    {
        // Arrange - create order
        $customer = $this->createCustomerWithToken('payment-fail-test@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Test Product', 10, '25.00', '30.75');

        $this->addItemToCart($customer, $product->getId(), 1);
        $this->placeOrder($customer, $address->getId());

        $orderResponse = json_decode($this->client->getResponse()->getContent(), true);
        $orderId = $orderResponse['id'];

        // Configure mock to fail - this should work with singleton
        $this->mockPaymentService->setShouldFailPay(true, 'Simulated payment error');

        // Act - HTTP request (the mock should be used)
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/order/pay/' . $orderId, [
            'returnUrl' => 'https://example.com/success',
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('message', $response);
        static::assertStringContainsString('Simulated payment error', $response['message']);
    }

    public function testPayEndpointThrowsException(): void
    {
        // Arrange - create order
        $customer = $this->createCustomerWithToken('payment-exception-test@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Test Product', 10, '25.00', '30.75');

        $this->addItemToCart($customer, $product->getId(), 1);
        $this->placeOrder($customer, $address->getId());

        $orderResponse = json_decode($this->client->getResponse()->getContent(), true);
        $orderId = $orderResponse['id'];

        // Configure mock to throw exception
        $this->mockPaymentService->setShouldThrowException(true, 'Payment service unavailable');

        // Act - HTTP request (static state should persist)
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/order/pay/' . $orderId, [
            'returnUrl' => 'https://example.com/success',
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('message', $response);
        static::assertStringContainsString('Payment service unavailable', $response['message']);
    }

    public function testPayEndpointFailsForNonExistentOrder(): void
    {
        // Arrange
        $customer = $this->createCustomerWithToken('test@example.com');

        // Act - try to pay for non-existent order
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/order/pay/999999', [
            'returnUrl' => 'https://example.com/success',
        ], [
            'HTTP_wsei-ecommerce-token' => $customer->getApiToken()->getToken(),
        ]);

        // Assert
        static::assertResponseStatusCodeSame(404);
    }

    public function testVerifyEndpointWithMockSuccess(): void
    {
        // Arrange - create order for verification context
        $customer = $this->createCustomerWithToken('verify-test@example.com');
        $address = $this->createAddress($customer);
        $product = $this->createProduct('Test Product', 10, '25.00', '30.75');

        $this->addItemToCart($customer, $product->getId(), 1);
        $this->placeOrder($customer, $address->getId());

        $orderResponse = json_decode($this->client->getResponse()->getContent(), true);
        $order = $this->orderRepository->find($orderResponse['id']);

        // Set up mock for successful verification (static state will persist)
        $this->mockPaymentService->setOrder($order);

        // Act - verify payment with any token (mock will handle)
        $this->client->request('GET', '/ecommerce/api/v1/order/verify-payment?token=mock_token_123');

        // Assert - should redirect to success
        static::assertResponseRedirects();
        $location = $this->client->getResponse()->headers->get('Location');
        static::assertStringContainsString('https://example.com/success', $location);
        static::assertStringContainsString('payment_success=1', $location);
    }

    public function testVerifyEndpointWithMockFailure(): void
    {
        // Configure mock to fail verification (static state will persist)
        $this->mockPaymentService->setShouldFailVerify(true, 'Payment was not completed');

        // Act - HTTP request
        $this->client->request('GET', '/ecommerce/api/v1/order/verify-payment?token=failed_token');

        // Assert - should return error (no redirect URL available for failed verification)
        static::assertResponseStatusCodeSame(404);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('message', $response);
        static::assertStringContainsString('Payment was not completed', $response['message']);
    }

    public function testVerifyEndpointWithoutToken(): void
    {
        // Act - verify without token
        $this->client->request('GET', '/ecommerce/api/v1/order/verify-payment');

        // Assert
        static::assertResponseStatusCodeSame(400);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('message', $response);
        static::assertStringContainsString('Payment token is required', $response['message']);
    }
}
