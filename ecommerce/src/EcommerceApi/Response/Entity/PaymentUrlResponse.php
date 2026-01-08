<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;

#[OA\Schema(
    schema: 'PaymentUrlResponse',
    properties: [
        new OA\Property(
            property: 'paymentUrl',
            type: 'string',
            format: 'uri',
            example: 'https://checkout.stripe.com/pay/cs_xxx'
        ),
        new OA\Property(property: 'token', type: 'string', example: 'payment_token_123'),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'PaymentResponse'),
    ]
)]
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
