<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Wsei\Ecommerce\Entity\Address;
use Wsei\Ecommerce\Entity\Customer;

trait BuildsAddresses
{
    abstract protected function getEntityManager(): EntityManagerInterface;

    protected function createAddress(
        Customer $customer,
        string $firstName = 'John',
        string $lastName = 'Doe',
        string $street = '123 Main St',
        string $zipcode = '12345',
        string $city = 'Test City',
        string $country = 'Test Country'
    ): Address {
        $address = new Address();
        $address->setCustomer($customer);
        $address->setFirstName($firstName);
        $address->setLastName($lastName);
        $address->setStreet($street);
        $address->setZipcode($zipcode);
        $address->setCity($city);
        $address->setCountry($country);

        $this->getEntityManager()->persist($address);
        $this->getEntityManager()->flush();

        return $address;
    }
}
