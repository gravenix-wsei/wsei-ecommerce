<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Payload;

use Symfony\Component\Validator\Constraints as Assert;

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
