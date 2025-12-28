<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Payload;

use Symfony\Component\Validator\Constraints as Assert;

class InitiatePaymentPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'Return URL is required')]
        #[Assert\Url(message: 'Return URL must be a valid URL', requireTld: false)]
        public readonly string $returnUrl,
    ) {
    }
}
