<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Payload;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'AddCartItemPayload',
    required: ['productId', 'quantity'],
    properties: [
        new OA\Property(property: 'productId', type: 'integer', example: 1, description: 'ID of the product to add'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2, description: 'Quantity to add'),
    ]
)]
class AddCartItemPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Product ID is required')]
        #[Assert\Type(type: 'integer', message: 'Product ID must be an integer')]
        #[Assert\Positive(message: 'Product ID must be positive')]
        public readonly int $productId,
        #[Assert\NotBlank(message: 'Quantity is required')]
        #[Assert\Type(type: 'integer', message: 'Quantity must be an integer')]
        #[Assert\Positive(message: 'Quantity must be positive')]
        public readonly int $quantity,
    ) {
    }
}
