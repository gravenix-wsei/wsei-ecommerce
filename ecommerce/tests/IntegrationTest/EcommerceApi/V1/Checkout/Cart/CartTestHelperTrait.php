<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Checkout\Cart;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Wsei\Ecommerce\Entity\ApiToken;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Entity\Product;

trait CartTestHelperTrait
{
    protected function createCustomerWithToken(string $email): Customer
    {
        $entityManager = $this->getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = $this->getContainer()->get(UserPasswordHasherInterface::class);

        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setFirstName('Test');
        $customer->setLastName('User');
        $customer->setPassword($passwordHasher->hashPassword($customer, 'password123'));

        $apiToken = new ApiToken();
        $apiToken->setToken(ApiToken::generate());
        $customer->setApiToken($apiToken);

        $entityManager->persist($customer);
        $entityManager->persist($apiToken);
        $entityManager->flush();

        return $customer;
    }

    protected function createProduct(string $name, int $stock, string $priceNet, ?string $priceGross = null): Product
    {
        $entityManager = $this->getContainer()->get(EntityManagerInterface::class);

        $product = new Product();
        $product->setName($name);
        $product->setDescription('Test description for ' . $name);
        $product->setStock($stock);
        $product->setPriceNet($priceNet);
        $product->setPriceGross($priceGross ?? $priceNet);

        $entityManager->persist($product);
        $entityManager->flush();

        return $product;
    }
}
