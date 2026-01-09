<?php

declare(strict_types=1);

namespace Wsei\Ecommerce\Tests\IntegrationTest\EcommerceApi\V1\Customer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Wsei\Ecommerce\Entity\Customer;
use Wsei\Ecommerce\Repository\CustomerRepository;

class CustomerRegisterControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
    }

    public function testRegisterCustomerSuccessfully(): void
    {
        // Arrange
        $payload = [
            'email' => 'newcustomer@example.com',
            'password' => 'password123',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/register', $payload);

        // Assert
        static::assertResponseStatusCodeSame(201);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertArrayHasKey('token', $response);
        static::assertArrayHasKey('expiresAt', $response);
        static::assertNotEmpty($response['token']);

        // Verify customer was created in database
        $customerRepository = $this->container->get(CustomerRepository::class);
        $customer = $customerRepository->findOneBy([
            'email' => 'newcustomer@example.com',
        ]);
        static::assertNotNull($customer);
        static::assertEquals('John', $customer->getFirstName());
        static::assertEquals('Doe', $customer->getLastName());
        static::assertNotNull($customer->getApiToken());
    }

    public function testRegisterCustomerWithDuplicateEmail(): void
    {
        // Arrange
        $this->createCustomer('duplicate@example.com');
        $payload = [
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'firstName' => 'Jane',
            'lastName' => 'Smith',
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/register', $payload);

        // Assert
        static::assertResponseStatusCodeSame(409);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        static::assertEquals('CUSTOMER_ALREADY_EXISTS', $response['errorCode']);
    }

    public function testRegisterCustomerWithMissingEmail(): void
    {
        // Arrange
        $payload = [
            'password' => 'password123',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/register', $payload);
        static::assertResponseStatusCodeSame(400);
        // Assert
        static::assertResponseStatusCodeSame(400);
    }

    public function testRegisterCustomerWithMissingPassword(): void
    {
        // Arrange
        $payload = [
            'email' => 'test@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/register', $payload);
        static::assertResponseStatusCodeSame(400);
        // Assert
        static::assertResponseStatusCodeSame(400);
    }

    public function testRegisterCustomerWithMissingFirstName(): void
    {
        // Arrange
        $payload = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'lastName' => 'Doe',
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/register', $payload);
        static::assertResponseStatusCodeSame(400);
        // Assert
        static::assertResponseStatusCodeSame(400);
    }

    public function testRegisterCustomerWithMissingLastName(): void
    {
        // Arrange
        $payload = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstName' => 'John',
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/register', $payload);

        // Assert
        static::assertResponseStatusCodeSame(400);
    }

    public function testRegisterCustomerWithInvalidEmail(): void
    {
        // Arrange
        $payload = [
            'email' => 'not-a-valid-email',
            'password' => 'password123',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/register', $payload);

        // Assert
        static::assertResponseStatusCodeSame(400);
    }

    public function testRegisterCustomerWithShortPassword(): void
    {
        // Arrange
        $payload = [
            'email' => 'test@example.com',
            'password' => 'short',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/register', $payload);

        // Assert
        static::assertResponseStatusCodeSame(400);
    }

    public function testRegisterCustomerWithEmptyFields(): void
    {
        // Arrange
        $payload = [
            'email' => '',
            'password' => '',
            'firstName' => '',
            'lastName' => '',
        ];

        // Act
        $this->client->jsonRequest('POST', '/ecommerce/api/v1/customer/register', $payload);

        // Assert
        static::assertResponseStatusCodeSame(400);
    }

    private function createCustomer(string $email): Customer
    {
        $entityManager = $this->container->get(EntityManagerInterface::class);
        $passwordHasher = $this->container->get(UserPasswordHasherInterface::class);

        $customer = new Customer();
        $customer->setEmail($email);
        $customer->setFirstName('Test');
        $customer->setLastName('User');
        $customer->setPassword($passwordHasher->hashPassword($customer, 'password123'));

        $entityManager->persist($customer);
        $entityManager->flush();

        return $customer;
    }
}
