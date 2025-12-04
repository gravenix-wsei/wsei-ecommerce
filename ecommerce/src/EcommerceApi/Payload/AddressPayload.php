<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\EcommerceApi\Payload;

use Symfony\Component\Validator\Constraints as Assert;
use Wsei\Ecommerce\Entity\Address;

class AddressPayload
{
    public function __construct(
        #[Assert\NotBlank(message: 'First name is required')]
        #[Assert\Length(max: Address::MAX_LENGTH_FIRST_NAME)]
        public readonly string $firstName,
        #[Assert\NotBlank(message: 'Last name is required')]
        #[Assert\Length(max: Address::MAX_LENGTH_LAST_NAME)]
        public readonly string $lastName,
        #[Assert\NotBlank(message: 'Street is required')]
        #[Assert\Length(max: Address::MAX_LENGTH_STREET)]
        public readonly string $street,
        #[Assert\NotBlank(message: 'Zipcode is required')]
        #[Assert\Length(max: Address::MAX_LENGTH_ZIPCODE)]
        public readonly string $zipcode,
        #[Assert\NotBlank(message: 'City is required')]
        #[Assert\Length(max: Address::MAX_LENGTH_CITY)]
        public readonly string $city,
        #[Assert\NotBlank(message: 'Country is required')]
        #[Assert\Length(max: Address::MAX_LENGTH_COUNTRY)]
        public readonly string $country,
    ) {
    }

    public function toAddress(): Address
    {
        return (new Address())
            ->setFirstName($this->firstName)
            ->setLastName($this->lastName)
            ->setStreet($this->street)
            ->setZipcode($this->zipcode)
            ->setCity($this->city)
            ->setCountry($this->country);
    }

    public function updateAddress(Address $address): void
    {
        $address
            ->setFirstName($this->firstName)
            ->setLastName($this->lastName)
            ->setStreet($this->street)
            ->setZipcode($this->zipcode)
            ->setCity($this->city)
            ->setCountry($this->country);
    }
}
