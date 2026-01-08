<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Payload;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'PlaceOrderPayload',
    required: ['addressId'],
    properties: [
        new OA\Property(property: 'addressId', type: 'integer', example: 1, description: 'ID of the delivery address'),
    ]
)]
class PlaceOrderPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Address ID is required')]
        #[Assert\Type(type: 'integer', message: 'Address ID must be an integer')]
        #[Assert\Positive(message: 'Address ID must be positive')]
        public readonly int $addressId,
    ) {
    }
}
