<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Framework\Payment\Result;

use Wsei\Ecommerce\Entity\Order;

class PaymentVerificationResult
{
    public function __construct(
        private readonly bool $success,
        private readonly ?Order $order,
        private readonly string $returnUrl,
        private readonly ?string $message = null
    ) {
    }

    public static function success(Order $order, string $returnUrl): self
    {
        return new self(true, $order, $returnUrl);
    }

    public static function failure(string $message, ?Order $order = null, string $returnUrl = ''): self
    {
        return new self(false, $order, $returnUrl, $message);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function hasReturnUrl(): bool
    {
        return !empty($this->returnUrl);
    }
}
