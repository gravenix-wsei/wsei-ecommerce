<?php declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\Mocked\Framework\Payment;

use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Framework\Payment\PaymentServiceInterface;
use Wsei\Ecommerce\Framework\Payment\Result\PaymentResult;
use Wsei\Ecommerce\Framework\Payment\Result\PaymentVerificationResult;

class MockPaymentService implements PaymentServiceInterface
{
    // Use static state to persist across HTTP requests in tests
    private static bool $shouldFailPay = false;
    private static bool $shouldFailVerify = false;
    private static bool $shouldThrowException = false;
    private static ?Order $order = null;
    private static string $failureMessage = 'Mock failure';

    /**
     * @throws \Exception
     */
    public function pay(Order $order, string $returnUrl): PaymentResult
    {
        if (self::$shouldThrowException) {
            throw new \Exception(self::$failureMessage);
        }

        if (self::$shouldFailPay) {
            throw new \InvalidArgumentException(self::$failureMessage);
        }

        return new PaymentResult(
            'https://mock.stripe.com/checkout/session_123',
            'mock_token_' . uniqid()
        );
    }

    public function verify(string $token): PaymentVerificationResult
    {
        if (self::$shouldThrowException) {
            throw new \Exception(self::$failureMessage);
        }

        if (self::$shouldFailVerify) {
            return PaymentVerificationResult::failure(self::$failureMessage);
        }

        if (self::$order === null) {
            return PaymentVerificationResult::failure('No order set');
        }

        // Mock success - no real order needed for simple testing
        return PaymentVerificationResult::success(self::$order, 'https://example.com/success');
    }

    public function setShouldFailPay(bool $fail, string $message = 'Mock pay failure'): void
    {
        self::$shouldFailPay = $fail;
        self::$failureMessage = $message;
    }

    public function setShouldFailVerify(bool $fail, string $message = 'Mock verify failure'): void
    {
        self::$shouldFailVerify = $fail;
        self::$failureMessage = $message;
    }

    public function setShouldThrowException(bool $throw, string $message = 'Mock exception'): void
    {
        self::$shouldThrowException = $throw;
        self::$failureMessage = $message;
    }

    public function setOrder(Order $order): void
    {
        self::$order = $order;
    }

    public function reset(): void
    {
        self::$shouldFailPay = false;
        self::$shouldFailVerify = false;
        self::$shouldThrowException = false;
        self::$failureMessage = 'Mock failure';
        self::$order = null;
    }
}
