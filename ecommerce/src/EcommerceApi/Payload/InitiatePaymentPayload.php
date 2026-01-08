<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Payload;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'InitiatePaymentPayload',
    required: ['returnUrl'],
    properties: [
        new OA\Property(property: 'returnUrl', type: 'string', format: 'uri', example: 'https://example.com/payment/callback', description: 'URL to redirect after payment'),
    ]
)]
class InitiatePaymentPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Return URL is required')]
        #[Assert\Url(message: 'Return URL must be a valid URL', requireTld: false)]
        public readonly string $returnUrl,
    ) {
    }
}
