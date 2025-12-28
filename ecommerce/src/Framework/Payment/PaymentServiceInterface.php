<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Payment;

use Wsei\Ecommerce\Entity\Order;
use Wsei\Ecommerce\Framework\Payment\Result\PaymentResult;
use Wsei\Ecommerce\Framework\Payment\Result\PaymentVerificationResult;

interface PaymentServiceInterface
{
    /**
     * Initiate payment for an order
     *
     * @param Order $order The order to create payment for
     * @param string $returnUrl URL to redirect after successful payment
     * @return PaymentResult Payment URL and verification token
     */
    public function pay(Order $order, string $returnUrl): PaymentResult;

    /**
     * Verify payment status using verification token
     *
     * @param string $token Payment verification token
     * @return PaymentVerificationResult Verification result with status and details
     */
    public function verify(string $token): PaymentVerificationResult;
}
