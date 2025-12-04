<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Address;

class AddressListResponse extends EcommerceResponse
{
    /**
     * @param iterable<Address> $addresses
     */
    public function __construct(
        private readonly iterable $addresses
    ) {
        parent::__construct();
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatData(): array
    {
        return [
            'addresses' => \array_map(
                fn (Address $address) => (new AddressResponse($address))->formatResponse(),
                $this->addresses
            ),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'AddressList';
    }
}
