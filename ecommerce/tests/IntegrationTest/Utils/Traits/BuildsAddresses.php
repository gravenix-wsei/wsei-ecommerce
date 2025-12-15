<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Wsei\Ecommerce\Entity\Address;
use Wsei\Ecommerce\Entity\Customer;

trait BuildsAddresses
{
    abstract protected function getEntityManager(): EntityManagerInterface;

    /**
     * @param array<string, string> $overrides
     */
    protected function createAddress(Customer $customer, array $overrides = []): Address
    {
        $address = new Address();
        $address->setCustomer($customer);
        $address->setFirstName($overrides['firstName'] ?? 'John');
        $address->setLastName($overrides['lastName'] ?? 'Doe');
        $address->setStreet($overrides['street'] ?? '123 Main St');
        $address->setZipcode($overrides['zipcode'] ?? '12345');
        $address->setCity($overrides['city'] ?? 'Test City');
        $address->setCountry($overrides['country'] ?? 'Test Country');

        $this->getEntityManager()->persist($address);
        $this->getEntityManager()->flush();

        return $address;
    }
}

