<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Payload;

use Symfony\Component\Validator\Constraints as Assert;

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
