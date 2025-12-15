<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\Utils\Traits;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Wsei\Ecommerce\Entity\ApiToken;
use Wsei\Ecommerce\Entity\Customer;

trait BuildsCustomers
{
    private static int $customerEmailCounter = 0;

    abstract protected function getEntityManager(): EntityManagerInterface;

    protected function createCustomer(
        ?string $email = null,
        string $firstName = 'Test',
        string $lastName = 'User',
        string $password = 'password123',
        bool $withApiToken = false
    ): Customer {
        if ($email === null) {
            ++self::$customerEmailCounter;
            $email = 'test-' . self::$customerEmailCounter . '@example.com';
        }

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setFirstName($firstName);
        $customer->setLastName($lastName);
        $customer->setPassword($passwordHasher->hashPassword($customer, $password));

        if ($withApiToken) {
            $apiToken = new ApiToken();
            $apiToken->setToken(ApiToken::generate());
            $customer->setApiToken($apiToken);
            $this->getEntityManager()->persist($apiToken);
        }

        $this->getEntityManager()->persist($customer);
        $this->getEntityManager()->flush();

        return $customer;
    }

    protected function createCustomerWithToken(?string $email = null): Customer
    {
        return $this->createCustomer(email: $email, withApiToken: true);
    }
}

