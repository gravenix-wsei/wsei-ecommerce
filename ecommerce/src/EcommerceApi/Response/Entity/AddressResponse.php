<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Response\Entity;

use OpenApi\Attributes as OA;
use Wsei\Ecommerce\EcommerceApi\Response\EcommerceResponse;
use Wsei\Ecommerce\Entity\Address;

#[OA\Schema(
    schema: 'AddressResponse',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'firstName', type: 'string', example: 'John'),
        new OA\Property(property: 'lastName', type: 'string', example: 'Doe'),
        new OA\Property(property: 'street', type: 'string', example: '123 Main St'),
        new OA\Property(property: 'zipcode', type: 'string', example: '12345'),
        new OA\Property(property: 'city', type: 'string', example: 'New York'),
        new OA\Property(property: 'country', type: 'string', example: 'USA'),
        new OA\Property(property: 'apiDescription', type: 'string', example: 'Address'),
    ]
)]
class AddressResponse extends EcommerceResponse
{
    public function __construct(
        private readonly Address $address
    ) {
        parent::__construct();
    }

    protected function formatData(): array
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
