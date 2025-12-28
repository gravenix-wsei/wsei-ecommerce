<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Payment\Result;

class PaymentResult
{
    public function __construct(
        private readonly string $paymentUrl,
        private readonly string $token
    ) {
    }

    public function getPaymentUrl(): string
    {
        return $this->paymentUrl;
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
