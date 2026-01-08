<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Depends;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Wsei\Ecommerce\Controller\EcommerceApi\V1\Customer\CustomerAuthController;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Repository\CustomerRepository;

class ExampleIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private CustomerRepository $customerRepository;

    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->customerRepository = self::getContainer()->get(CustomerRepository::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testCustomerLoginController(): void
    {
        // Create test customer
        $customer = new Customer();
        $customer->setEmail('login-test@example.com')
            ->setFirstName('Login')
            ->setLastName('Test');

        // Hash the password properly
        $hashedPassword = $this->passwordHasher->hashPassword($customer, 'password123');
        $customer->setPassword($hashedPassword);

        $this->entityManager->persist($customer);
        $this->entityManager->flush();

        // Test login controller
        $loginController = self::getContainer()->get(CustomerAuthController::class);

        $request = new Request(content: \json_encode([
            'email' => 'login-test@example.com',
            'password' => 'password123',
        ]));

        $response = $loginController->login($request);

        static::assertEquals(200, $response->getStatusCode());
        static::assertNotNull($response->getContent());
    }

    #[Depends('testCustomerLoginController')]
    public function testDatabaseIsRolledBackBetweenTests(): void
    {
        // This test verifies that the customer from previous test doesn't exist
        // because DAMA bundle rolls back transactions after each test
        $loginCustomer = $this->customerRepository->findOneBy([
            'email' => 'login-test@example.com',
        ]);
        static::assertNull($loginCustomer, 'Login customer should not exist - database should be rolled back');
    }
}
