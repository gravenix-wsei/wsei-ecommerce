<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;

class PaymentUrlResponse extends EcommerceResponse
{
    public function __construct(
        private readonly string $paymentUrl,
        private readonly string $token
    ) {
        parent::__construct();
    }

    protected function formatData(): array
    {
        return [
            'paymentUrl' => $this->paymentUrl,
            'token' => $this->token,
        ];
    }

    protected function getApiDescription(): string
    {
        return 'PaymentResponse';
    }
}
