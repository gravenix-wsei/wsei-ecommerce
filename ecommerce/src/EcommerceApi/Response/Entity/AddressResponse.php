<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Address;

class AddressResponse extends EcommerceResponse
{
    public function __construct(
        private readonly Address $address
    ) {
        parent::__construct();
    }

    protected function formatResponse(): array
    {
        return [
            'id' => $this->address->getId(),
            'firstName' => $this->address->getFirstName(),
            'lastName' => $this->address->getLastName(),
            'street' => $this->address->getStreet(),
            'zipcode' => $this->address->getZipcode(),
            'city' => $this->address->getCity(),
            'country' => $this->address->getCountry(),
        ];
    }

    protected function getApiDescription(): string
    {
        return 'Address';
    }
}
